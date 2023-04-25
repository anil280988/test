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

class SurveyForm extends FormBase {

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
        return 'survey_form';
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

  
        $form['description3'] = [
            '#type' => 'item',
            '#markup' => $this->t('Organization Information'),
        ];


        $form['org_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Organization Name <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['org_short_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Organization Short Name <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['address'] = array(
            '#type' => 'textarea',
            '#attributes' => array(
                'class' => array('form-control'),
             'required' => TRUE
            ),
            '#title' => $this->t('Address <span class="req">*</span>'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['city'] = array(
            '#type' => 'textfield',
            '#title' => t('City <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
             'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['zipcode'] = array(
            '#type' => 'number',
            '#title' => t('Zipcode <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
             'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['contact_phone'] = array(
            '#type' => 'number',
            '#title' => t('Contact Phone <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
            'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['contact_fax'] = array(
            '#type' => 'textfield',
            '#title' => t('Contact Fax'),
            '#attributes' => array(
                'class' => array('form-control')
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['contact_email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Contact Email <span class="req">*</span>'),
        ];

        $form['primary_contact'] = array(
            '#type' => 'textfield',
            '#title' => t('Primary Contact <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
               'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['primary_phone'] = array(
            '#type' => 'number',
            '#title' => t('Primary Phone <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
                'required' => TRUE
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['secondary_email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Secondary Email <span class="req">*</span>'),
        ];
        $form['secondary_contact'] = array(
            '#type' => 'textfield',
            '#title' => t('Secondary Contact <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $countrylist = \Drupal::service('country_manager')->getList();
        $countries = array_values($countrylist);
        $form['country'] = [
            '#type' => 'select',
            '#title' => t('Country <span class="req">*</span>'),
            '#attributes' => array(
                'class' => array('form-control'),
             'required' => TRUE
            ),
            //'#wrapper_attributes' => ['class' => 'col-12'],
            '#options' => array_combine($countries, $countries),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        ];

        $form['domains'] = [
            '#type' => 'url',
            '#title' => $this->t('Domains'),
            '#attributes' => array(
                'class' => array('form-control')
            ),
            '#url' => '',
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        ];

        $form['cloud_solution'] = [
            '#type' => 'checkboxes',
            '#attributes' => array(
            //'class' => array('form-control'),
            //'required' => TRUE
            ),
            //'#multiple' => TRUE,
            '#options' => array('Amazon Web Services' => $this->t('Amazon Web Services'), 'Microsoft Azure' => $this->t('Microsoft Azure')),
            '#title' => $this->t('Cloud Solution <span class="req">*</span><div class="hr-devide"><hr></div>'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        ];

        $form['description1'] = [
            '#type' => 'item',
            '#markup' => $this->t('Identify and assign owners for each of the following sections of the onboarding questionnaire'),
        ];

        $form['all_section'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
            'class' => array('all-section'),
            //'required' => TRUE
            ),
            '#title' => $this->t('All sections filling by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        ];        

        $form['label'] = array(
            '#type' => 'item',
            '#markup' => t('Or, Assign this section to'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );

        $form['#tree'] = TRUE;
        $form['section1'] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div id="section1-wrapper">',
            '#suffix' => '</div>',
        );
       $form['section1']['mefill'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
             'class' => array('mefill section1-mefill'),
             'checked' => TRUE,
            ),
            '#title' => $this->t('This section to be filled by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
             '#states' => array(
                'unchecked' => array(
                    ':input[name="section1[first_name]"]' => array('filled' => TRUE),
                ),
            ),
        ];  
        $form['section1']['label'] = array(
            '#type' => 'item',
            '#markup' => t('Organization Information'),
            '#prefix' => '<div class ="form-group sectionlabel">',
            '#suffix' => '</div>',
        );
        $form['section1']['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section1[first_name]"]' => array('filled' => FALSE),
                ),
              'required' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section1']['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //  'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section1[last_name]"]' => array('filled' => FALSE),
                ),
                'required' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => FALSE),
                ),

            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section1']['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            //  'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section1[email]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section1[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email'),
        ];

        $form['section2'] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div id="section2-wrapper">',
            '#suffix' => '</div>',
        );
        $form['section2']['label'] = array(
            '#type' => 'item',
            '#markup' => t('User Information'),
            '#prefix' => '<div class ="form-group sectionlabel">',
            '#suffix' => '</div>',
        );
   $form['section2']['mefill'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
              'class' => array('mefill section2-mefill'),
              'checked' => TRUE,
            ),
            '#title' => $this->t('This section to be filled by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
             '#states' => array(
                'unchecked' => array(
                    ':input[name="section2[first_name]"]' => array('filled' => TRUE),
                ),
            ),
        ];
        $form['section2']['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //  'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section2[first_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section2']['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section2[last_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section2']['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section2[email]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section2[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email'),
        ];
        $form['section3'] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div id="section3-wrapper">',
            '#suffix' => '</div>',
        );
        $form['section3']['label'] = array(
            '#type' => 'item',
            '#markup' => t('Cloud Provider'),
            '#prefix' => '<div class ="form-group sectionlabel">',
            '#suffix' => '</div>',
        );
   $form['section3']['mefill'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
              'class' => array('mefill section3-mefill'),
              'checked' => TRUE,
            ),
            '#title' => $this->t('This section to be filled by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
             '#states' => array(
                'unchecked' => array(
                    ':input[name="section3[first_name]"]' => array('filled' => TRUE),
                ),
            ),
        ];
        $form['section3']['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section3[first_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section3']['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
             '#states' => array(
                'disabled' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section3[last_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section3']['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
             '#states' => array(
                'disabled' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section3[email]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section3[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email'),
        ];

        $form['section4'] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div id="section4-wrapper">',
            '#suffix' => '</div>',
        );
        $form['section4']['label'] = array(
            '#type' => 'item',
            '#markup' => t('Monitoring Tools'),
            '#prefix' => '<div class ="form-group sectionlabel">',
            '#suffix' => '</div>',
        );
   $form['section4']['mefill'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
              'class' => array('mefill section4-mefill'),
              'checked' => TRUE,
            ),
            '#title' => $this->t('This section to be filled by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
             '#states' => array(
                'unchecked' => array(
                    ':input[name="section4[first_name]"]' => array('filled' => TRUE),
                ),
            ),
        ];
        $form['section4']['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
           '#states' => array(
                'disabled' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section4[first_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section4']['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
             '#states' => array(
                'disabled' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section4[last_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section4']['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
             '#states' => array(
                'disabled' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section4[email]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section4[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email'),
        ];
        $form['section5'] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div id="section5-wrapper">',
            '#suffix' => '</div>',
        );
        $form['section5']['label'] = array(
            '#type' => 'item',
            '#markup' => t('Domain Account Information'),
            '#prefix' => '<div class ="form-group sectionlabel">',
            '#suffix' => '</div>',
        );
   $form['section5']['mefill'] = [
            '#type' => 'checkbox',
            '#attributes' => array(
              'class' => array('mefill section5-mefill'),
              'checked' => TRUE,
            ),
            '#title' => $this->t('This section to be filled by me'),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
             '#states' => array(
                'unchecked' => array(
                    ':input[name="section5[first_name]"]' => array('filled' => TRUE),
                ),
            ),
        ];
        $form['section5']['first_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('First Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
             '#states' => array(
                'disabled' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => TRUE),
                      'or' ,
                    ':input[name="section5[first_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section5']['last_name'] = array(
            '#type' => 'textfield',
            '#size' => '80',
            '#title' => t('Last Name'),
            '#attributes' => array(
                'class' => array('form-control'),
            // 'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section5[last_name]"]' => array('filled' => FALSE),
                ),
               'required' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
        );
        $form['section5']['email'] = [
            '#type' => 'email',
            '#attributes' => array(
                'class' => array('form-control'),
            //'required' => TRUE
            ),
            '#states' => array(
                'disabled' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => TRUE),
                     'or' ,
                    ':input[name="section5[email]"]' => array('filled' => FALSE),
                     
                ),
               'required' => array(
                    ':input[name="section5[mefill]"]' => array('checked' => FALSE),
                ),
            ),
            '#prefix' => '<div class ="form-group">',
            '#suffix' => '</div>',
            '#title' => $this->t('Email'),
        ];
        $form['description2'] = [
            '#type' => 'item',
            '#markup' => $this->t('An email will be sent to each of the above owners'),
        ];
        $form['hidden_tkvalue'] = array(
            '#type' => 'hidden',
            '#default_value' => 'token',
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Continue'),
//      '#button_type' => 'primary',
            '#attributes' => array(
                'class' => array('btn btn-primary continue-button'),
            ),
            '#prefix' => '<ul class="nav navbar-nav navbar-right"><li>',
            '#suffix' => '</li></ul>',

        ];
        $form['#theme'] = 'surveyform';
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
        parent::validateForm($form, $form_state);
        $zipcode = $form_state->getValue('zipcode');
        if (strlen($zipcode) < 5 || strlen($zipcode) > 6 ) {
           $form_state->setErrorByName('zipcode', $this->t('Please enter the valid zipcode.'));
        }
        $contact_phone = $form_state->getValue('contact_phone');
        if (strlen($contact_phone) < 10 || strlen($contact_phone) > 12) {
            $form_state->setErrorByName('contact_phone', $this->t('Please enter valid phone number.'));
       }
       $primary_phone = $form_state->getValue('primary_phone');
        if (strlen($primary_phone) < 10 || strlen($primary_phone) > 12) {
            $form_state->setErrorByName('primary_phone', $this->t('Please enter valid phone number.'));
       }
       $cloud_soultions = $form_state->getValue('cloud_solution');
        if( empty ($cloud_soultions['Amazon Web Services']) &&  empty ($cloud_soultions['Microsoft Azure']) ) {
            $form_state->setErrorByName('cloud_solution', $this->t('<div class="form-item--error-message checkbox-errors">Cloud solution is required</div>'));
        }
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
        $user_id = \Drupal::currentUser()->id();
        $user_entity = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);       
        $submit_data = $form_state->getValues();

        $ip = \Drupal::request()->getClientIp();
        $page = \Drupal::request()->getRequestUri();
        $host = \Drupal::request()->getHost();
        \Drupal::logger('survey form data')->info("SUCCESS! survey form data: <pre>" . print_r($submit_data, true) . "</pre>");

        $cloud_solutions =  array_filter($submit_data['cloud_solution']);


        // Create webform submission.
        $webform_id = 'onboarding';
        $webform = \Drupal\webform\Entity\Webform::load($webform_id);
        \Drupal::logger('survey webform id')->info("SUCCESS! survey webform id: <pre>" . print_r($cloud_solutions, true) . "</pre>");



        $values = [
            'webform_id' => 'onboarding',
            'uri' => '/onboarding-form',
            'current_page' => 'foundational_questions',
            'uid' =>  $user_id,
            'entity_type' => 'node',
            'entity_id' => 76,
            'data' => [
                'organization_name' => $submit_data['org_name'],
                'org_short_name' => $submit_data['org_short_name'],
                'address' => $submit_data['address'],
                'city' => $submit_data['city'],
                'zipcode' => $submit_data['zipcode'],
                'contact_phone' => $submit_data['contact_phone'],
                'contact_fax' => $submit_data['contact_fax'],
                'contact_email' => $submit_data['contact_email'],
                'primary_contact' => $submit_data['primary_contact'],
                'primary_phone' => $submit_data['primary_phone'],
                'secondary_email' => $submit_data['secondary_email'],
                'secondary_contact' => $submit_data['secondary_contact'],
                'country' => $submit_data['country'],
                'domains' => $submit_data['domains'],
                'cloud_solutions' => array_values($cloud_solutions)
            ],
        ];
        //$webform_submission = WebformSubmissionForm::submitFormValues($values);
        $webform_submission = WebformSubmission::create($values);
        // $webform_submission->isNew();
        $webform_submission->set('in_draft', TRUE);
        $webform_submission->save();
        $webid = $webform_submission->id();
        $webdata = $webform_submission->getTokenUrl();
        //getTokeN
        $token = $webdata->getOptions()['query']['token'];
         \Drupal::logger('web data')->info("SUCCESS! web data: <pre>" . print_r($webdata->getOptions()['query']['token'] , true) . "</pre>");
      
         // create users
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $email_array = array();
        $data = array(1, 2, 3, 4, 5);
        $sections[1] = 'Organization Information';
        $sections[2] = 'User Information';
        $sections[3] = 'Cloud Provider';
        $sections[4] = 'Monitoring Tools';
        $sections[5] = 'Domain Account Information';
        foreach ($data as $val) {
            $key = 'section' . $val;
            if (!empty($submit_data[$key]['first_name']) && !empty($submit_data[$key]['email'])) {

                $email_array[$key]['email'] = $submit_data[$key]['email'];
                $email_array[$key]['name'] = !empty($submit_data[$key]['last_name']) ? $submit_data[$key]['first_name'] . " " . $submit_data[$key]['last_name'] : $submit_data[$key]['first_name'];
                $email_array[$key]['page'] = $val;

                $ids = \Drupal::entityQuery('user')
                        ->condition('mail', $submit_data[$key]['email'])
                        ->execute();

                if (empty($ids)) {
                    $user = \Drupal\user\Entity\User::create();
                    //Mandatory settings
                    $user->setPassword('test');
                    $user->enforceIsNew();
                    $user->setEmail($submit_data[$key]['email']);
                    $user->setUsername($submit_data[$key]['email']);
                    $user->addRole('concierto_wizard');
                    $user->set("field_first_name", $submit_data[$key]['first_name']);
                    $user->set("field_last_name", $submit_data[$key]['last_name']);
                    $user->activate();
                    //Save user
                    $new_user = $user->save();

                    $langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();
                    //$usernew = user_load_by_mail($submit_data[$key]['email']);
                       // $uid = $usernew->id();
                        \Drupal::logger('user data')->info("SUCCESS! user data: <pre>" . print_r($usernew, true) . "</pre>");
                        
                       // $account = \Drupal\user\Entity\User::load($uid);
                        //$users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(array('mail' => $submit_data[$key]['email']));
                        //$account = reset($users);
                         // Set operation.
                        //$op = 'register_no_approval_required';
                       // $op = 'register_admin_created';
                        // Send an email.
                        //_user_mail_notify($op, $account);
                }

                 $query = \Drupal::database()->insert('survey_form');
                 $query->fields(['first_name', 'last_name', 'email', 'section_no', 'section_name',  'sid', 'token', 'is_owner', 'is_completed', 'created', 'ip', 'assigned_by']);
                    $query->values([
                        'first_name' => $submit_data[$key]['first_name'],
                        'last_name' => $submit_data[$key]['last_name'],
                        'email' => $submit_data[$key]['email'],
                        'section_no' => $val,
                        'section_name' => $sections[$val],
                        'sid'=> $webid,
                        'token' => $token,
                        'is_owner' => $user_entity->get('mail')->value == $submit_data[$key]['email'] ? 1 : 0,
                        'is_completed' => $val == 1 ? 9 : 0,
                        'created' => time(),
                        'ip' => $ip,
                        'assigned_by' => $user_id
                    ]);                
                 $query->execute();
            } else {
                    $query = \Drupal::database()->insert('survey_form');
                      $query->fields(['first_name', 'last_name', 'email', 'section_no', 'section_name', 'sid', 'token', 'is_owner', 'is_completed', 'created', 'ip', 'assigned_by']);
                    $query->values([
                        'first_name' => $user_entity->get('field_first_name')->value,
                        'last_name' => $user_entity->get('field_last_name')->value,
                        'email' => $user_entity->get('mail')->value,
                        'section_no' => $val,
                        'section_name' => $sections[$val],
                        'sid'=> $webid,
                        'token' => $token,
                        'is_owner' =>  1,
                        'is_completed' => $val == 1 ? 9 : 0,
                        'created' => time(),
                        'ip' => $ip,
                        'assigned_by' => $user_id
                    ]);                    
                   $query->execute();
                }
        }
        \Drupal::logger('email array data')->info("SUCCESS! email data: <pre>" . print_r($email_array, true) . "</pre>");
     
             if (!empty($email_array)) {
                //send mail
                try {
                    $mailManager = \Drupal::service('plugin.manager.mail');
                    $module = 'survey_webforms';
                    $key = 'sent_transferemail';

                    foreach ($email_array as $email) {
                        $account = user_load_by_mail($email['email']);
                        $reset_link=user_pass_reset_url($account);

                        $to = $email['email'];
                        $payload['email'] = $email['email'];
                        $payload['url'] = $reset_link;
                        $payload['full_name'] = $email['name'];
                        $payload['assigned_by'] = $user_entity->get('field_first_name')->value.' '.$user_entity->get('field_last_name')->value;
                        $payload['role'] = 1;
                        //$page = $email['page'];
                        //$payload['url'] = $base_url . '/onboarding-form?page=' . $page . '&token=' . $token;
                        $params['payload'] = $payload;
                        $langcode = \Drupal::currentUser()->getPreferredLangcode();
                        $send = true;

                        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
                        if ($result['result'] !== true) {
                            \Drupal::messenger()->addMessage(t('There was a problem sending your message and it was not sent.'), 'error', TRUE);
                        } else {
                            \Drupal::messenger()->addMessage(t('Your message has been sent.'), 'success', TRUE);
                        }
                    }
                } catch (\Exception $e) {
                    \Drupal::logger('mail error')->error("ERROR from" . __FILE__ . ":" . __LINE__ . " " . $e->getMessage());
                }
            }


        // Redirect to home
        $response = new RedirectResponse($base_url . '/survey');
        $response->send();
    }

}
