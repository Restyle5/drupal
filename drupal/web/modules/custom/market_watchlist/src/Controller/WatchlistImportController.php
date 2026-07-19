<?php

namespace Drupal\market_watchlist\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles POST /api/watchlist/import.
 *
 * Accepts a JSON array of price objects and upserts them efficiently into
 * market_watchlist_prices using bulk compilation.
 */
class WatchlistImportController extends ControllerBase {

  /**
   * Maximum number of entries accepted in a single payload.
   */
  protected const MAX_ENTRIES = 5000;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * A logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * The time service.
   */
  protected TimeInterface $time;

  /**
   * Constructs the controller.
   */
  public function __construct(Connection $database, LoggerInterface $logger, TimeInterface $time) {
    $this->database = $database;
    $this->logger = $logger;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')->get('market_watchlist'),
      $container->get('datetime.time')
    );
  }

  /**
   * Imports a JSON array of price entries using an optimized batch strategy.
   */
  public function import(Request $request): JsonResponse {
    // Utilize Symfony's format resolver for safer API content negotiation.
    if ($request->getContentTypeFormat() !== 'json') {
      return new JsonResponse(['error' => 'Content-Type must be application/json.'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    $raw = $request->getContent();
    if (empty($raw)) {
      return new JsonResponse(['error' => 'Empty request body.'], Response::HTTP_BAD_REQUEST);
    }

    $data = json_decode($raw, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(['error' => 'Invalid JSON: ' . json_last_error_msg()], Response::HTTP_BAD_REQUEST);
    }
    if (!is_array($data)) {
      return new JsonResponse(['error' => 'JSON payload must be an array of entries.'], Response::HTTP_BAD_REQUEST);
    }
    if (count($data) > self::MAX_ENTRIES) {
      return new JsonResponse(['error' => sprintf('Payload exceeds the maximum of %d entries.', self::MAX_ENTRIES)], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
    }

    $skipped = [];
    $processedData = [];
    $now = $this->time->getRequestTime();

    // Step 1: Validate and deduplicate in memory first.
    // "Last occurrence wins" means we overwrite the key in our staging array before hitting the DB.
    foreach ($data as $index => $entry) {
      $errors = $this->validateEntry($entry);
      if (!empty($errors)) {
        $skipped[] = [
          'index' => $index,
          'errors' => $errors,
        ];
        continue;
      }

      $symbol = strtoupper(trim((string) $entry['symbol']));
      $processedData[$symbol] = [
        'symbol' => $symbol,
        'price' => (float) $entry['price'],
        'change_amount' => (float) $entry['change'],
        'volume' => (int) $entry['volume'],
        'updated_at' => $now,
      ];
    }

    $upsertedCount = 0;

    // Step 2: Perform a high-efficiency Bulk Upsert operation.
    if (!empty($processedData)) {
      try {
        // Drupal's upsert query compiles down to a single INSERT ... ON DUPLICATE KEY UPDATE (MySQL)
        // or MERGE (PostgreSQL/Oracle), changing N round-trips down to exactly 1.
        $query = $this->database->upsert('market_watchlist_prices')
          ->key('symbol')
          ->fields(['symbol', 'price', 'change_amount', 'volume', 'updated_at']);

        foreach ($processedData as $row) {
          $query->values($row);
        }

        $query->execute();
        $upsertedCount = count($processedData);

        // Clear target cache tags to refresh display components safely.
        Cache::invalidateTags(['market_watchlist_prices']);
      }
      catch (\Exception $e) {
        $this->logger->error('Bulk import failed completely: @message', ['@message' => $e->getMessage()]);
        return new JsonResponse(['error' => 'An internal database error occurred during bulk save.'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }

    return new JsonResponse([
      'status' => 'ok',
      'total_received' => count($data),
      'upserted_total' => $upsertedCount,
      'skipped_count' => count($skipped),
      'skipped' => $skipped,
    ]);
  }

  /**
   * Validates a single decoded JSON entry.
   */
  protected function validateEntry($entry): array {
    $errors = [];

    if (!is_array($entry)) {
      return ['Entry must be a JSON object.'];
    }

    if (empty($entry['symbol']) || !is_string($entry['symbol'])) {
      $errors[] = 'Missing or invalid "symbol".';
    }
    elseif (!preg_match('/^[A-Za-z0-9.\-]{1,20}$/', trim($entry['symbol']))) {
      $errors[] = '"symbol" contains invalid characters or is too long.';
    }

    foreach (['price', 'change', 'volume'] as $field) {
      if (!isset($entry[$field]) || !is_numeric($entry[$field])) {
        $errors[] = sprintf('Missing or invalid "%s".', $field);
      }
    }

    if (isset($entry['volume']) && is_numeric($entry['volume']) && $entry['volume'] < 0) {
      $errors[] = '"volume" cannot be negative.';
    }
    if (isset($entry['price']) && is_numeric($entry['price']) && $entry['price'] < 0) {
      $errors[] = '"price" cannot be negative.';
    }

    return $errors;
  }

}