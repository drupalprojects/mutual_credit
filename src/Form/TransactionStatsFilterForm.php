<?php

namespace Drupal\mcapi\Form;

Use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for forms.
 */
class TransactionStatsFilterForm extends FormBase {

  protected $entityTypeManager;

  /**
   * Constructor
   *
   * @param EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transaction_stats_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $args = func_get_args();
    $currency = $form_state->get('currency');
    $previous = isset($_SESSION['transaction_stats_filter']) ?  $_SESSION['transaction_stats_filter'] : [];

    $form['since'] = [
      '#title' => $this->t('Since'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('All time'),
      ],
      '#default_value' => isset($previous['since']) ? $previous['since'] : 0
    ];
    $form['until'] = [
      '#title' => $this->t('Until'),
      '#type' => 'select',
      '#empty_option' => t('Now'),
      '#required' => FALSE,
      '#default_value' => isset($previous['until']) ? $previous['until'] : REQUEST_TIME
    ];
    $earliest = $currency->firstUsed();
    $next_year = strtotime('01-01-'.date('Y', $earliest));
    $next_month = strtotime('01-'. date('M', $earliest) .'-'.date('Y', $earliest));

    if ($next_year >= strtotime('01-01-'.date('Y'))) {
      // System is less than one year old, so show months
      while ($next_month < REQUEST_TIME) {
        $this->plus1month($previous_month, $next_month);
        $form['since']['#options'][$previous_month.':'.$next_month] = date('M', $previous_month) .', '.date('Y', $previous_month);
      }
    }
    else {
      //$form['since']['#options'][$next_year] = date('Y', $next_year);
      while ($next_year < REQUEST_TIME) {
        $this->plus1year($previous_year, $next_year);
        $form['since']['#options'][$previous_year] = date('Y', $previous_year);
        $form['until']['#options'][$next_year] = date('Y', $next_year);
      }
    }

    $form['type'] = [
      '#type' => 'mcapi_types',
      '#required' => FALSE,
      '#default_value' => isset($previous['type']) ? $previous['type'] : '',
      '#empty_option' => $this->t('- Any -')
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#weight' => 20
    ];
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#limit_validation_errors' => [],
      '#submit' => ['::resetForm'],
      '#weight' => 21
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $val) {
      $_SESSION['transaction_stats_filter'][$key] = $val;
    }
  }

  public function resetForm(array &$form, FormStateInterface $form_state) {
    unset($_SESSION['transaction_stats_filter']);
  }

  public function plus1month(&$previous_month, &$next_month) {
    $previous_month = $next_month;
    $days_in_month = date('t', $next_month);
    $next_month = strtotime('+ '.$days_in_month. '  days', $next_month);
  }
  public function plus1year(&$previous_year, &$next_year) {
    $previous_year = $next_year;
    $days_in_year = date('L', $next_year) ? 366 : 365;
    $next_year = strtotime('+ '.$days_in_year. '  days', $next_year);
  }

}
