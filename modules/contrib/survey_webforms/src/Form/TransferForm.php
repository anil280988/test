<?php

namespace Drupal\survey_webforms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransferForm extends FormBase {

    /**
     * Returns a unique string identifying the form.
     *
     * The returned ID should be a unique string that can be a valid PHP function
     * name, since it's used in hook implementation names such as
     * hook_form_FORM_ID_alter().
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'tranfer_form';
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {


        $form['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
               // 'placeholder' => 'email *',
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => 'Email <span class="req">*</span>',
        ];

        $form['hidden_page'] = array(
            '#type' => 'hidden',
            '#default_value' => 'page',
        );

        $form['hidden_sidvalue'] = array(
            '#type' => 'hidden',
            '#default_value' => 'sid',
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Transfer Section'),
//      '#button_type' => 'primary',
            '#attributes' => array(
                'class' => array('btn btn-primary'),
            ),
        ];
        return $form;
    }

    /**
     * Validate the title and the checkbox of the form
     * 
     * @param array $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * 
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        global $base_url;
        $submit_data = $form_state->getValues();
        \Drupal::logger('survey form data')->info("SUCCESS! survey form data: <pre>" . print_r($submit_data, true) . "</pre>");
        $ip = \Drupal::request()->getClientIp();
        $user_id = \Drupal::currentUser()->id();
        $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
        $page = \Drupal::request()->getRequestUri();
        $host = \Drupal::request()->getHost();
        $pattern = "/-/";
        try {
            $ids = \Drupal::entityQuery('user')
                    ->condition('mail', $submit_data['email'])
                    ->execute();

            if (empty($ids)) {
                $user = \Drupal\user\Entity\User::create();
                //Mandatory settings
                $user->setPassword('test');
                $user->enforceIsNew();
                $user->setEmail($submit_data['email']);
                $user->setUsername($submit_data['email']);
                $user->addRole('concierto_wizard');
                $user->set("field_first_name", $submit_data['first_name']);
                $user->set("field_last_name", $submit_data['last_name']);
                $user->activate();
                //Save user
                $new_user = $user->save();

                // assign new transfee

                $query = \Drupal::database()->update('survey_form');
                $query->fields([
                    'first_name' => $submit_data['first_name'],
                    'last_name' => $submit_data['last_name'],
                    'email' => $submit_data['email'],
                ]);
                $query->condition('sid', $submit_data['hidden_sidvalue']);
                $query->condition('section_no', $submit_data['hidden_page']);
                $query->execute();

                
            } else {
                $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
                $query = \Drupal::database()->update('survey_form');
                $query->fields([
                    'first_name' => $submit_data['first_name'],
                    'last_name' => $submit_data['last_name'],
                    'email' => $submit_data['email'],
                ]);
                $query->condition('sid', $submit_data['hidden_sidvalue']);
                $query->condition('section_no', $submit_data['hidden_page']);
                $query->execute();
            }
 
         //send mail
                try {
                    $mailManager = \Drupal::service('plugin.manager.mail');
                    $module = 'survey_webforms';
                    $key = 'sent_transferemail';

                    $account = user_load_by_mail($submit_data['email']);
                    $reset_link = user_pass_reset_url($account);

                    $to = $submit_data['email'];
                    $payload['email'] = $submit_data['email'];
                    $payload['url'] = $reset_link;
                    $payload['full_name'] = !empty($submit_data['last_name']) ? $submit_data['first_name'] . " " . $submit_data['last_name'] : $submit_data['first_name'];
                    $payload['assigned_by'] = $user_entity->get('field_first_name')->value . ' ' . $user_entity->get('field_last_name')->value;
                    $payload['role'] = 1;
                    $params['payload'] = $payload;
                    $langcode = \Drupal::currentUser()->getPreferredLangcode();
                    $send = true;

                    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
                    if ($result['result'] !== true) {
                        \Drupal::messenger()->addMessage(t('There was a problem sending your message and it was not sent.'), 'error', TRUE);
                    } else {
                        \Drupal::messenger()->addMessage(t('Your message has been sent.'), 'success', TRUE);
                    }
                } catch (\Exception $e) {
                    \Drupal::logger('adsf')->error("ERROR from" . __FILE__ . ":" . __LINE__ . " " . $e->getMessage());
                }         

         } catch (\Exception $e) {

            $contents = $e->getMessage();
        }
    }

}
