<?php
/**
 * @file
 * Admin configuration management
 */

include_once INTEL_DIR . 'includes/intel.wizard.php';

include_once INTEL_DIR . 'admin/intel.admin_setup.php';

function wpcf7_intel_admin_setup_wizard_info($items = array()) {

  $info = array(
    'title' => __('Contact Form 7 GA Intelligence setup'),
    'un' => 'wpcf7_intel_setup',
    'callback_prefix' => 'wpcf7_intel_admin_setup',
    'steps' => array(),
  );

  $info['steps']['intel_plugin'] = array(
    'title' => Intel_Df::t('Intelligence plugin'),
    'action_img_src' => INTEL_URL . '/images/setup_base_ga_action.png',
  );

  $info['steps']['intel_profile'] = array(
    'title' => Intel_Df::t('Intelligence connect'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );

  $info['steps']['default_tracking'] = array(
    'title' => __('Default tracking', 'wpcf7_intel'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );


  $info['steps']['finish'] = array(
    'title' => Intel_Df::t('Finish'),
    'submit_button_text' => '',
    'completed' => 1,
  );

  return $info;
}

function wpcf7_intel_admin_setup_page() {
  $wizard_info = wpcf7_intel_admin_setup_wizard_info();
  $form = Intel_Form::drupal_get_form('intel_wizard_form', $wizard_info);
  return Intel_Df::render($form);
}

function wpcf7_intel_admin_setup_intel_plugin($form, &$form_state) {
  $f = array();

  include_once wpcf7_intel()->dir . 'wpcf7_intel.setup.php';

  $instructions = wpcf7_intel_setup()->get_intel_install_instructions();

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => $instructions,
  );

  return $f;
}

function wpcf7_intel_admin_setup_intel_plugin_check($form, &$form_state) {
  include_once INTEL_DIR . 'includes/intel.ga.php';

  $status = array();

  if (is_callable('intel')) {
    $status['success'] = 1;
  }
  else {
    $status['error_msg'] = Intel_Df::t('Intelligence plugin has not been activated.');
    $status['error_msg'] .= ' ' . Intel_Df::t('Please install and activate before proceeding.');
  }

  return $status;
}

function wpcf7_intel_admin_setup_intel_plugin_validate($form, &$form_state) {
  if (!empty($status['error_msg'])) {
    Intel_Form::form_set_error('none', $status['error_msg']);
  }
}

function wpcf7_intel_admin_setup_intel_profile($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  $options = array(
    'imapi_property_setup' => array(
      'callback_destination' => 'admin/config/intel/settings/setup/wpcf7_intel',
    ),
  );
  return intel_admin_setup_intel_profile($form, $form_state, $options);
}

function wpcf7_intel_admin_setup_intel_profile_check($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_check($form, $form_state);
}

function wpcf7_intel_admin_setup_intel_profile_validate($form, &$form_state, $status) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_validate($form, $form_state, $status);
}

function wpcf7_intel_admin_setup_intel_profile_submit($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.php';
  return intel_admin_setup_intel_profile_submit($form, $form_state);
}

function wpcf7_intel_admin_setup_default_tracking($form, &$form_state) {
  $f = array();

  $items = array();

  $text_domain = 'wpcf7_intel';

  $items[] = '<p>';
  $items[] = __('You can custom configure tracking per form.', $text_domain);
  $items[] = __('Default tracking is used as a fallback for any form that does not have custom configuration to assure all forms are tracked.', $text_domain);
  $items[] = '</p>';
  $items[] = '<br>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  $f['default'] = array(
    '#type' => 'fieldset',
    '#title' => __('Default form tracking', $text_domain),
    '#collapsible' => FALSE,
  );

  $f['default']['inline_wrapper_1'] = array(
    '#type' => 'markup',
    '#markup' => '<div class="pull-left">',
  );

  // load goals in case ga profile already has goals
  $options = array(
    'index_by' => 'ga_id',
    'refresh' => !empty($_GET['refresh']) && is_numeric($_GET['refresh']) ? intval($_GET['refresh']) : 3600,
  );

  $form_state['intel_goals'] = $goals = intel_goal_load(null, $options);
  $form_state['intel_ga_goals'] = $ga_goals = intel_ga_goal_load();

  $eventgoal_options = intel_get_form_submission_eventgoal_options('default');
  $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path());
  $f['default']['intel_form_track_submission_default'] = array(
    '#type' => 'select',
    '#title' => Intel_Df::t('Submission event/goal'),
    '#options' => $eventgoal_options,
    '#default_value' => get_option('intel_form_track_submission_default', 'form_submission'),
    '#description' => __('Select the goal or event you would like to trigger to be tracked in analytics when a form is submitted.', $text_domain),
    '#suffix' => '<div class="add-goal-link text-right" style="margin-top: -12px;">' . Intel_Df::l(Intel_Df::t('Add Goal'), 'admin/config/intel/settings/goal/add', $l_options) . '</div>',
  );

  $f['default']['inline_wrapper_2'] = array(
    '#type' => 'markup',
    '#markup' => '</div><div class="clearfix"></div>',
  );

  $desc = __('Set a value to be passed with the default goal. Leave blank to use the goal default value.', $text_domain);
  $f['default']['intel_form_track_submission_value_default'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Submission value'),
    '#default_value' => get_option('intel_form_track_submission_value_default', ''),
    '#description' => $desc,
    '#size' => 8,
  );

  return $f;
}

function wpcf7_intel_admin_setup_default_tracking_check($form, &$form_state) {
  $status = array();

  $event_name = get_option('intel_form_track_submission_default', -1);
  if (isset($form_state['values']['intel_form_track_submission_default'])) {
    $event_name = $form_state['values']['intel_form_track_submission_default'];
  }

  if ($event_name != -1) {
    $status['success'] = 1;
  }

  return $status;
}

function wpcf7_intel_admin_setup_default_tracking_validate($form, &$form_state, $status) {

}

function wpcf7_intel_admin_setup_default_tracking_submit($form, &$form_state) {
  update_option('intel_form_track_submission_default', $form_state['values']['intel_form_track_submission_default']);
  update_option('intel_form_track_submission_value_default', $form_state['values']['intel_form_track_submission_value_default']);
}

function wpcf7_intel_admin_setup_finish($form, &$form_state) {
  $f = array();

  $markup = '';
  $markup .= '<div class="row">';
  $markup .= '<div class="col-xs-7">';
  $f['markup_0'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  $items = array();

  $items[] = '<div class="text-center">';
  $items[] = '<h3>' . Intel_Df::t('Congratulations') . '</h3>';

  $items[] = '<p>';
  $items[] = __('Your Contact Form 7 submissions are now being tracked!', 'wpcf7_intel');
  $items[] = '</p>';

  $items[] = '<p>';
  $items[] = '<strong>' . Intel_Df::t('Go ahead, give it a try:') . '</strong>';
  $l_options = Intel_Df::l_options_add_class('btn btn-info');
  $items[] = '<br>' . Intel_Df::l( Intel_Df::t('Test your form tracking'), 'admin/help/demo/wpcf7_intel', $l_options);
  //$items[] = '<br>' . Intel_Df::t('(click on tracked links and forms to trigger events)');
  $items[] = '</p>';


  $items[] = '<p>';
  $items[] = Intel_Df::t(__('You can customize form tracking in !extends_link settings or view the !intel_link.', 'wpcf7_intel'),
    array(
      '!extends_link' => Intel_Df::l(__('Contact Form 7', 'wpcf7_intel'), 'admin.php?page=wpcf7'),
      '!intel_link' => Intel_Df::l(__('Intelligence form settings list', 'wpcf7_intel'), 'admin/config/intel/settings/form'),
    ));
  $items[] = '</p>';

  $items[] = '</div>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  $markup = '';
  $markup .= '</div>';
  $markup .= '<div class="col-xs-5">';
  $markup .= '<image src="' . INTEL_URL . '/images/setup_finish_right.png" class="img-responsive" >';
  $markup .= '</div>';
  $markup .= '</div>';
  $f['markup_1'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  // clear gf_setup as active setup wizard
  update_option('intel_setup', array());

  return $f;
}



