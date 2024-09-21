<?php
/**
 * @File
 * A form to collect an email for RSVP details
 */

 namespace Drupal\omdexam\Form;

 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;

 class MovieListingSettingsForm extends FormBase{

    function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);
    
        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(){
        return 'movie_listings_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state){
        $form['submit'] =[
            '#type' => 'submit',
            '#value' => t("Manual Import"),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        try{
            $headers = []; 
            $body = 'NotNull';
            $url = 'http://localhost:3000/movies';
            $client = \Drupal::httpClient();
            $res = $client->request('GET', $url, $headers, $body);
            $jsonMovie = json_decode($res->getBody(), true);

            //Insert json data into the following db tables
            $connection = \Drupal::service('database');

            //node_field_data (title)
            foreach($jsonMovie as $movie){
                $result = $connection->insert('node_field_data')
                    ->fields([
                    'nid' => (int)$movie["id"],
                    'vid' => (int)$movie["id"],
                    'type' => 'moviepost',
                    'langcode' => 'en',
                    'status' => 1,
                    'uid' => (int)$movie["id"],
                    'title' => $movie["title"],
                    'created' => (int)$movie["timestamp"],
                    'changed' => (int)$movie["timestamp"],
                    'promote' => 1,
                    'sticky' => 0,
                    'default_langcode' => 0,
                    'revision_translation_affected' => 1,
                ])->execute();
            }

            //node_body (body)
            foreach($jsonMovie as $movie){
                $result = $connection->insert('node__body')
                    ->fields([
                    'bundle' => "moviepost",
                    'deleted' => 0,
                    'entity_id' => (int)$movie["id"],
                    'revision_id' => (int)$movie["id"],
                    'langcode' => "en",
                    'delta' => 0,
                    'body_value' => "<p>" . $movie["synopsis"] . "</p>",
                    'body_summary' => '',
                    'body_format' => "basic_html",
                ])->execute();
            }

            //node__movie_director
            foreach($jsonMovie as $movie){
                $result = $connection->insert('node__field_movie_director')
                    ->fields([
                    'bundle' => "moviepost",
                    'deleted' => 0,
                    'entity_id' => (int)$movie["id"],
                    'revision_id' => (int)$movie["id"],
                    'langcode' => "en",
                    'delta' => 0,
                    'field_movie_director_value' => $movie["director"],
                ])->execute();
            }

            //node__movie_release_date
            foreach($jsonMovie as $movie){
                $result = $connection->insert('node__field_movie_release_date')
                    ->fields([
                    'bundle' => "moviepost",
                    'deleted' => 0,
                    'entity_id' => (int)$movie["id"],
                    'revision_id' => (int)$movie["id"],
                    'langcode' => "en",
                    'delta' => 0,
                    'field_movie_release_date_value' => $movie["release"],
                ])->execute();
            }
 
            \Drupal::messenger()->addMessage('Thank you\n Status');
            \Drupal::logger('omdexam')->notice(strval($res->getStatusCode()) . " | " . strval($res->getBody()) . " | " . var_export($jsonMovie, true));
        }catch(\Exception $e){
            \Drupal::messenger()->addMessage(t("Error fetching"));
            \Drupal::logger('omdexam')->notice("Fetch Error: " . $e->getMessage());
        }
        
    }
 }