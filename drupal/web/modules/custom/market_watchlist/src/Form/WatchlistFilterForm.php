<?php

namespace Drupal\market_watchlist\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simple symbol filter for the watchlist display page.
 *
 * This is a GET form: submitting it re-requests /watchlist with a
 * ?symbol=... query argument, so no CSRF token is required and the
 * filter is bookmarkable/shareable.
 */
class WatchlistFilterForm extends FormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStackWatchList;

  /**
   * Constructs the form.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStackWatchList = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'market_watchlist_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStackWatchList->getCurrentRequest();
    $current_value = $request ? (string) $request->query->get('symbol', '') : '';

    // GET form: no token needed, values travel as a query string.
    $form['#method'] = 'get';
    $form['#token'] = FALSE;

    $form['symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by symbol'),
      '#default_value' => $current_value,
      '#size' => 20,
      '#attributes' => ['placeholder' => $this->t('e.g. AAPL')],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    if ($current_value !== '') {
      $form['actions']['reset'] = [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#url' => Url::fromRoute('market_watchlist.view'),
        '#attributes' => ['class' => ['button']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty: this is a GET form, the browser performs the
    // redirect with the query string itself.
  }

}
