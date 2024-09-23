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
        // Get previously imported moviepost nodes from 
        $query = \Drupal::database()->select('omdexam_last_import', 't');
        $query->fields('t', ['title']);
        $query->fields('t', ['import_time']);
        $result = $query->execute()->fetchAll();

        $htmlMarkup = '<div><h1>' . 'Last imported nodes: ' . '</h1><ul>';
        foreach($result as $movie){
            $htmlMarkup = $htmlMarkup  . '<li>' . $movie->title . ' @ ' . $movie->import_time . '</li>';
        }
        $htmlMarkup = $htmlMarkup . '</ul>';

        $form['submit'] =[
            '#type' => 'submit',
            '#value' => t("Manual Import"),
        ];
        $form['html'] =[
            '#markup' => $htmlMarkup,
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
            /*
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
            */
            $newlyImportedMovies = []; 
            // Iterate through jason stream
            foreach($jsonMovie as $movie){
                // Request moviepost object with the current movie_uid of the json stream object we are iterating on
                $nodes = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties([
                    'type' => 'moviepost',
                    'field_movie_uid' => $movie['id'],
                ]);
                // If the node doesnt already exist in the database, import it
                if ( !($node = reset($nodes))) {
                    $data = [
                        'type' => 'moviepost', 
                        'title' => $movie["title"], 
                        'field_movie_release_date' => $movie["release"],
                        'field_movie_director' => $movie["director"],
                        'body' => $movie["synopsis"],
                        'uid' => (int)$movie["id"],
                        'field_movie_uid' => $movie['id']
                    ];
                    $node = \Drupal::entityTypeManager()
                    ->getStorage('node')
                    ->create($data);
                    $node->save();
                    
                    // Add title and timestamp to newlyImportedMovies for display on admin page
                    $current_time = \Drupal::time()->getRequestTime();
                    $dateOutput = date('F d, Y', $current_time); 
                    $formattedDate = $dateOutput . " @ " . date("h:i:a");
                    array_push($newlyImportedMovies, ['id' => $movie['id'], 'title' => $movie['title'], 'timestamp' => $formattedDate]);
                    
                }else{ // A moviepost node already exists with this json stream objects movie_uid, lets not import it to avoid duplicates
                    \Drupal::messenger()->addMessage('Database entry already exists with ID: ' . $movie['id'] . " | ". $movie['title']); // . ' | Dump: ' . var_export($nodes, true)
                }
            }
            // Add newly imported moviepost objects to 'omdexam_last_import' table for display on admin page (if there are any)
            if(count($newlyImportedMovies) != 0){
                //Delete all previously stored moviepost nodes from old import and update with new ones 
                // Get Drupal Database Connection as you can, or get using this.
                $connection = \Drupal::database();
                $num_deleted = $connection->delete('omdexam_last_import')
                ->execute();

                $connection = \Drupal::service('database');
                foreach($newlyImportedMovies as $movie){
                    $result = $connection->insert('omdexam_last_import')
                    ->fields([
                    'movie_uid' => $movie['id'],
                    'title' => $movie['title'],
                    'import_time' => $movie['timestamp'],
                    ])->execute();
                }
            }
            
            $current_time = \Drupal::time()->getRequestTime();
            $dateOutput = date('F d, Y', $current_time); 
            \Drupal::messenger()->addMessage('Thank you\n Time: ' . $dateOutput . " @ " . date("h:i:a"));
            //\Drupal::logger('omdexam')->notice(strval($res->getStatusCode()) . " | " . strval($res->getBody()) . " | " . var_export($jsonMovie, true));
        }catch(\Exception $e){
            \Drupal::messenger()->addMessage(t("Error fetching"));
            \Drupal::logger('omdexam')->notice("Fetch Error: " . $e->getMessage());
        }
    }
 }