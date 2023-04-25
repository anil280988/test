<?php

namespace Drupal\xss_prevention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config Form for XSS Prevention.
 */
class XssPreventionConfigForm extends ConfigFormBase {

  /**
   * List of default characters to check in URL.
   */
  const XSS_PREVENTION_DEFAULT_CHARACTERS = '%3e,%3c,>,<';

  /**
   * List of default JS event handlers to check in URL.
   */
  const XSS_PREVENTION_DEFAULT_JS_EVENTS = 'onmouseover,fscommand,onabort,onactivate,onafterprint,onafterupdate,onbeforeactivate,onbeforecopy,onbeforecut,onbeforedeactivate,onbeforeeditfocus,onbeforepaste,onbeforeprint,onbeforeunload,onbeforeupdate,onbegin,onblur,onbounce,oncellchange,onchange,onclick,oncontextmenu,oncontrolselect,oncopy,oncut,ondataavailable,ondatasetchanged,ondatasetcomplete,ondblclick,ondeactivate,ondrag,ondragend,ondragleave,ondragenter,ondragover,ondragdrop,ondragstart,ondrop,onend,onerror,onerrorupdate,onfilterchange,onfinish,onfocus,onfocusin,onfocusout,onhashchange,onhelp,oninput,onkeydown,onkeypress,onkeyup,onlayoutcomplete,onload,onlosecapture,onmediacomplete,onmediaerror,onmessage,onmousedown,onmouseenter,onmouseleave,onmousemove,onmouseout,onmouseover,onmouseup,onmousewheel,onmove,onmoveend,onmovestart,onoffline,ononline,onoutofsync,onpaste,onpause,onpopstate,onprogress,onpropertychange,onreadystatechange,onredo,onrepeat,onreset,onresize,onresizeend,onresizestart,onresume,onreverse,onrowsenter,onrowexit,onrowdelete,onrowinserted,onscroll,onseek,onselect,onselectionchange,onselectstart,onstart,onstop,onstorage,onsyncrestored,onsubmit,ontimeerror,ontrackchange,onundo,onunload,onurlflip,seeksegmenttime';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'xss_prevention.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xss_prevention_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xss_prevention.settings');

    $form['xss_prevention_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable XSS prevention'),
      '#description' => $this->t('If enabled, the module will check XSS attacks from URL.'),
      '#default_value' => $config->get('xss_prevention_enable'),
      '#weight' => '0',
    ];

    $form['xss_prevention_characters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of characters to check in URL'),
      '#default_value' => $config->get('xss_prevention_characters') ?? self::XSS_PREVENTION_DEFAULT_CHARACTERS,
      '#description' => $this->t('Comma-separated list of characters to check in URL.'),
      '#required' => TRUE,
      '#weight' => '0',
    ];

    $form['xss_prevention_js_events'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of JS event handlers to check in URL'),
      '#default_value' => $config->get('xss_prevention_js_events') ?? self::XSS_PREVENTION_DEFAULT_JS_EVENTS,
      '#description' => $this->t('Comma-separated list of JS event handlers to check in URL. Default list from <a href="https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet#Event_Handlers" target="_blank">https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet#Event_Handlers</a>'),
      '#required' => TRUE,
      '#weight' => '0',
    ];

    $form['xss_prevention_routes_white_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of routes to be excluded from control'),
      '#default_value' => $config->get('xss_prevention_routes_white_list') ?? '',
      '#description' => $this->t('Specify routes to be excluded from control. Enter one route per line.'),
      '#weight' => '0',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('xss_prevention.settings');
    $config->set('xss_prevention_enable', $form_state->getValue('xss_prevention_enable'));
    $config->set('xss_prevention_characters', $form_state->getValue('xss_prevention_characters'));
    $config->set('xss_prevention_js_events', $form_state->getValue('xss_prevention_js_events'));
    $config->set('xss_prevention_routes_white_list', $form_state->getValue('xss_prevention_routes_white_list'));

    $config->save();
  }

}
