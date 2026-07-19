<?php

namespace Drupal\market_watchlist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\market_watchlist\Form\WatchlistFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Displays the stored watchlist prices in a filterable table.
 */
class WatchlistController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilderService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs the controller.
   */
  public function __construct(Connection $database, FormBuilderInterface $form_builder, RequestStack $request_stack, DateFormatterInterface $date_formatter) {
    $this->database = $database;
    $this->formBuilderService = $form_builder;
    $this->requestStack = $request_stack;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('date.formatter')
    );
  }

  /**
   * Builds the /watchlist page.
   *
   * @return array
   *   A render array.
   */
  public function view(): array {
    $request = $this->requestStack->getCurrentRequest();
    $symbol_filter = $request ? trim((string) $request->query->get('symbol', '')) : '';

    $query = $this->database->select('market_watchlist_prices', 'w')
      ->fields('w', ['symbol', 'price', 'change_amount', 'volume', 'updated_at']);

    if ($symbol_filter !== '') {
      // escapeLike() + a placeholder-bound LIKE condition keeps this safe
      // from SQL injection; nothing is string-concatenated into the query.
      $query->condition('symbol', '%' . $this->database->escapeLike($symbol_filter) . '%', 'LIKE');
    }

    $query->orderBy('symbol', 'ASC');
    $results = $query->execute()->fetchAll();

    $header = [
      $this->t('Symbol'),
      $this->t('Price'),
      $this->t('Change'),
      $this->t('Volume'),
      $this->t('Last updated'),
    ];

    $rows = [];
    foreach ($results as $record) {
      $change = (float) $record->change_amount;
      $change_class = $change > 0 ? 'positive' : ($change < 0 ? 'negative' : 'neutral');

      $rows[] = [
        'data' => [
          ['data' => ['#plain_text' => $record->symbol]],
          ['data' => ['#plain_text' => number_format((float) $record->price, 2)]],
          [
            'data' => ['#plain_text' => number_format($change, 2)],
            'class' => [$change_class],
          ],
          ['data' => ['#plain_text' => number_format((int) $record->volume)]],
          ['data' => ['#plain_text' => $this->dateFormatter->format((int) $record->updated_at, 'short')]],
        ],
      ];
    }

    $build['filter'] = $this->formBuilderService->getForm(WatchlistFilterForm::class);

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No prices found.'),
      '#attributes' => ['class' => ['market-watchlist-table']],
    ];

    $build['#cache']['contexts'] = ['url.query_args:symbol'];
    $build['#cache']['tags'] = ['market_watchlist_prices'];

    return $build;
  }

}
