<?php
 
namespace Drupal\dhl\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
 
class LocationFinder extends FormBase {
 public function getFormId() {
   // Here we set a unique form id
   return 'locationfinder_form';
 }
 
 public function buildForm(array $form, FormStateInterface $form_state, $username = NULL) {
    // Textfield form element.
   $form['country'] = array(
     '#type' => 'textfield',
     '#title' => t('Country :'),
     '#required' => TRUE,
   );
    // Textfield form element.
   $form['city'] = array(
     '#type' => 'textfield',
     '#title' => t('City :'),
     '#required' => TRUE,
   );
   $form['postal_code'] = array(
    '#type' => 'textfield',
    '#title' => t('City :'),
    '#required' => TRUE,
  );
    // Textfield form element.
  
   //submit button.
   $form['actions']['submit'] = array(
     '#type' => 'submit',
     '#value' => $this->t('Save'),
     '#button_type' => 'primary',
   );
   return $form;
 }
 public function validateForm(array &$form, FormStateInterface $form_state) {
   /*if (strlen($form_state->getValue('postalcode')) < 2) {
     $form_state->setErrorByName('postalcode', $this->t('Postal Code too short.'));
   }*/
 }


 public function downloadItemTypeExport($filename) {

  // Do some file validation here, like checking for extension.

  // File lives in /files/downloads.
  $uri_prefix = 'public://downloads/';

  $uri = $uri_prefix . $filename;

  $headers = [
    'Content-Type' => 'text/yaml', // Would want a condition to check for extension and set Content-Type dynamically
    'Content-Description' => 'File Download',
    'Content-Disposition' => 'attachment; filename=' . $filename
  ];

  // Return and trigger file donwload.
  return new BinaryFileResponse($uri, 200, $headers, true );

}
 
 public function submitForm(array &$form, FormStateInterface $form_state) {
   \Drupal::messenger()->addMessage($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('country'))));
   /*foreach ($form_state->getValues() as $key => $value) {
     \Drupal::messenger()->addMessage($key . ': ' . $value);

   }*/
  
    
    $country = $form['country'];
    $city = $form['city'];
    $postcode = $form['postal_code'];

     $url = ' https://api.dhl.com/location-finder/v1/find-by-address?countryCode='.$country.'&addressLocality='.$city;

     $curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
		"DHL-API-Key: demo-key"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {

  \Drupal::messenger()->addMessage("cURL Error #:" . $err);

} else {
	
  //\Drupal::messenger()->addMessage( $response);

  $arrayVar = json_decode($response, TRUE);

  $totalcount = count($arrayVar["locations"]);

  for($i=0;$i<$totalcount;$i++)
  {
    $filename = $arrayVar["locations"][$i]["name"].".yaml";
    $myfile = fopen($filename, "w") or die("Unable to open file!");
    $txt = "URL : ".$arrayVar["locations"][$i]["url"];
    
    fwrite($myfile, $txt);
    $txt  = "distance : ".$arrayVar["locations"][$i]["distance"]."\n";	
    $txt .= "name : ".$arrayVar["locations"][$i]["name"]."\n";
    $txt .= "location : \n";
    
    $txt .= "\t ids : \n";
    $txt .= "\t \t locationId : ".$arrayVar["locations"][$i]["location"]['ids'][0]['locationId']."\n";
    $txt .= "\t \t provider : ".$arrayVar["locations"][$i]["location"]['ids'][0]['provider']."\n";
    $txt .= "\t \t keyword : ".$arrayVar["locations"][$i]["location"]['keyword']."\n";
    $txt .= "\t \t keywordId : ".$arrayVar["locations"][$i]["location"]['keywordId']."\n";
    $txt .= "\t \t type : ".$arrayVar["locations"][$i]["location"]['type']."\n\n";
    
    $txt .= "distance : ".$arrayVar["locations"][$i]["distance"]."\n";
    
    $txt .= "\n place : \n";
    $txt .= "\n \t address : \n \t\t countryCode : ".$arrayVar["locations"][$i]["place"]['address']['countryCode']."\n";
    $txt .= "\t\t postalCode : ".$arrayVar["locations"][$i]["place"]['address']['postalCode']."\n";
    $txt .= "\t\t addressLocality : ".$arrayVar["locations"][$i]["place"]['address']['addressLocality']."\n";
    $txt .= "\t\t streetAddress : ".$arrayVar["locations"][$i]["place"]['address']['streetAddress']."\n\n";
    
    $txt .= "\n \t geo : \n \t\t latitude : ".$arrayVar["locations"][$i]["place"]['geo']['latitude']."\n";
    $txt .= "\t\t longitude : ".$arrayVar["locations"][$i]["place"]['geo']['longitude']."\n";
    $txt .= "\n\nopeningHours : \n";
    
    $opendayscount = count($arrayVar["locations"][$i]["openingHours"]);
    
    
    
    for($k=0;$k<$opendayscount;$k++)
      {	
    
      $dayOfWeek = $arrayVar["locations"][$i]["openingHours"][$k]['dayOfWeek'];
      $dayOfWeek = trim(str_replace("http://schema.org/","",$dayOfWeek));
        
      $txt .= "\t\t".$dayOfWeek."  ".$arrayVar["locations"][$i]["openingHours"][$k]['opens']."  ".$arrayVar["locations"][$i]["openingHours"][$k]['closes']."\n";
      
        if(preg_match("/Sunday/i", $arrayVar["locations"][$i]["openingHours"][$k]['dayOfWeek']) || preg_match("/Saturday/i", $arrayVar["locations"][$i]["openingHours"][$k]['dayOfWeek']))
        {
          if(($arrayVar["locations"][$i]["openingHours"][$k]['opens'] == "00:00:00") || ($arrayVar["locations"][$i]["openingHours"][$k]['closes'] == "00:00:00"))
          {
          
            unset($value['openingHours'][$k]);
          }
        }
      }
    fwrite($myfile, $txt);	
    fclose($myfile);	
    $this->downloadItemTypeExport($myfile);
  }




}
    
    $form_state->setRedirect('<front>');
 }
}