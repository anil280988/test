<?php

namespace Drupal\survey_webforms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;

class CreateUserForm extends FormBase {

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
        //$form_id = $this->entity->getEntityTypeId();
        return 'createuser_form';
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
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email <span class="req">*</span>'),
        ];


        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
//      '#button_type' => 'primary',
            '#attributes' => array(
                'class' => array('btn btn-primary'),
            ),
        ];
        $form['#theme'] = 'createuserform';
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
	    
	   $login_user = User::load(\Drupal::currentUser()->id());//
//echo "<pre>";print_r($login_user);exit; 
	   
        try {

                        $user = \Drupal\user\Entity\User::create();
                        //Mandatory settings
                        $user->setPassword('test');
                        $user->enforceIsNew();
                        $user->setEmail($submit_data['email']);
                        $user->setUsername($submit_data['email']);
                        $user->addRole('whirlpool_editor');
                        $user->set("field_first_name", $submit_data['first_name']);
                        $user->set("field_last_name", $submit_data['last_name']);
						$user->set("field_domain_access", "whirlpool_apidevuser_concierto_cloud");
						
                        $user->activate();
                        $user_new = $user->save();
                        
                         $langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();
                          $account = user_load_by_mail($submit_data['email']);
                          $reset_link=user_pass_reset_url($account);
                     

                       //$uid = $usernew->id();
                       // \Drupal::logger('user data')->info("SUCCESS! user data: <pre>" . print_r($langcode, true) . "</pre>");
                        
                         //$account = \Drupal\user\Entity\User::load($uid);
                         
                       // $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(array('mail' => $submit_data['email']));
                         //$account = reset($users);
                         // Set operation.
                        //$op = 'register_no_approval_required';
                       // $op = 'register_admin_created';
                         //$op = 'password_reset';

                        // Send an email.
                      //  _user_mail_notify($op, $account, $langcode);

                       //send mail
            try {
                $mailManager = \Drupal::service('plugin.manager.mail');
                $module = 'survey_webforms';
                $key = 'sent_transferemail';

                $to = $submit_data['email'];
                $payload['email'] = $submit_data['email'];
                $payload['url'] = $reset_link;
                $payload['full_name'] = $submit_data['first_name']." " .$submit_data['last_name'];
                //$payload['banner_image'] = 'http://stg.concierto.cloud/themes/concierto/images/cc-kyndryl.png';
                $payload['role'] = 0;
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
