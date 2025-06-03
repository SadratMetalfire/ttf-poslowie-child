<?php
// Register CPT 'posel'
function posel_register_cpt()
{
    $labels = [
        'name'                  => 'Posłowie',
        'singular_name'         => 'Poseł',
        'menu_name'             => 'Posłowie',
        'add_new'               => 'Dodaj posła',
        'add_new_item'          => 'Dodaj nowego posła',
        'edit_item'             => 'Edytuj posła',
        'new_item'              => 'Nowy poseł',
        'view_item'             => 'Zobacz posła',
        'search_items'          => 'Szukaj posłów',
        'not_found'             => 'Nie znaleziono posłów',
        'not_found_in_trash'    => 'Brak posłów w koszu',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'posel'],
        'show_in_rest'       => true,
        'supports'           => ['title', 'thumbnail'],
        'capability_type'    => 'post',
        'menu_icon'          => 'dashicons-groups',
        'hierarchical'       => false,
    ];

    register_post_type('posel', $args);
}
add_action('init', 'posel_register_cpt');


// Register ACF fields locally
add_action('acf/init', function () {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group([
            'key' => 'group_posel_fields',
            'title' => 'Dane posła',
            'fields' => [
                ['key' => 'field_api_id', 'label' => 'API ID', 'name' => 'api_id', 'type' => 'number'],
                ['key' => 'field_first_name', 'label' => 'Imię', 'name' => 'first_name', 'type' => 'text'],
                ['key' => 'field_second_name', 'label' => 'Drugie imię', 'name' => 'second_name', 'type' => 'text'],
                ['key' => 'field_last_name', 'label' => 'Nazwisko', 'name' => 'last_name', 'type' => 'text'],
                ['key' => 'field_email', 'label' => 'Email', 'name' => 'email', 'type' => 'email'],
                ['key' => 'field_birth_date', 'label' => 'Data urodzenia', 'name' => 'birth_date', 'type' => 'date_picker'],
                ['key' => 'field_birth_location', 'label' => 'Miejsce urodzenia', 'name' => 'birth_location', 'type' => 'text'],
                ['key' => 'field_club', 'label' => 'Klub', 'name' => 'club', 'type' => 'text'],
                ['key' => 'field_district_name', 'label' => 'Okręg wyborczy (nazwa)', 'name' => 'district_name', 'type' => 'text'],
                ['key' => 'field_district_num', 'label' => 'Okręg wyborczy (numer)', 'name' => 'district_num', 'type' => 'number'],
                ['key' => 'field_education_level', 'label' => 'Poziom wykształcenia', 'name' => 'education_level', 'type' => 'text'],
                ['key' => 'field_profession', 'label' => 'Zawód', 'name' => 'profession', 'type' => 'text'],
                ['key' => 'field_voivodeship', 'label' => 'Województwo', 'name' => 'voivodeship', 'type' => 'text'],
                ['key' => 'field_number_of_votes', 'label' => 'Liczba głosów', 'name' => 'number_of_votes', 'type' => 'number'],
                ['key' => 'field_photo_url', 'label' => 'URL zdjęcia', 'name' => 'photo_url', 'type' => 'text'],
            ],
            'location' => [
                [
                    ['param' => 'post_type', 'operator' => '==', 'value' => 'posel']
                ]
            ],
            'style' => 'seamless',
        ]);
    }
});


// Make ACF fields readonly using CSS.
// In my opinion, imported data like this shouldn't be editable,
// so I added a simple optional implementation for that.
// add_action('current_screen', function ($screen) {
//     if ($screen->post_type === 'posel') {
//         echo '<style>
//             .acf-field[data-name="first_name"] input,
//             .acf-field[data-name="second_name"] input,
//             .acf-field[data-name="last_name"] input,
//             .acf-field[data-name="api_id"] input,
//             .acf-field[data-name="email"] input,
//             .acf-field[data-name="birth_date"] input,
//             .acf-field[data-name="birth_location"] input,
//             .acf-field[data-name="club"] input,
//             .acf-field[data-name="district_name"] input,
//             .acf-field[data-name="district_num"] input,
//             .acf-field[data-name="education_level"] input,
//             .acf-field[data-name="profession"] input,
//             .acf-field[data-name="voivodeship"] input,
//             .acf-field[data-name="number_of_votes"] input,
//             .acf-field[data-name="photo_url"] input  {
//                 background-color: #f0f0f0 !important;
//                 pointer-events: none !important;
//             }
//         </style>';
//     }
// });


// Import posłów z API Sejmu (kadencja domyślnie 10)
function import_poslow_from_api($term = 10, $limit = 0)
{
    $term = intval($term);
    $api_url = "https://api.sejm.gov.pl/sejm/term{$term}/MP";

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        error_log('Błąd pobierania danych z API: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $poslowie = json_decode($body, true);

    if (!is_array($poslowie)) {
        error_log('Niepoprawny format danych z API');
        return false;
    }

    $count = 0;

    foreach ($poslowie as $posel) {
        if ($limit > 0 && $count >= $limit) {
            break;
        }

        if (empty($posel['id'])) {
            continue;
        }

        $post_title = $posel['firstLastName'] ?? $posel['lastFirstName'] ?? 'Bez nazwiska';

        $existing = get_posts([
            'post_type'      => 'posel',
            'meta_key'       => 'api_id',
            'meta_value'     => $posel['id'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        $post_id = !empty($existing) ? $existing[0] : 0;

        $post_data = [
            'post_title'  => $post_title,
            'post_type'   => 'posel',
            'post_status' => 'publish',
        ];

        if ($post_id) {
            $post_data['ID'] = $post_id;
            wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id) || !$post_id) {
            continue;
        }

        update_field('api_id', $posel['id'] ?? '', $post_id);
        update_field('first_name', $posel['firstName'] ?? '', $post_id);
        update_field('second_name', $posel['secondName'] ?? '', $post_id);
        update_field('last_name', $posel['lastName'] ?? '', $post_id);
        update_field('email', $posel['email'] ?? '', $post_id);
        update_field('birth_date', $posel['birthDate'] ?? '', $post_id);
        update_field('birth_location', $posel['birthLocation'] ?? '', $post_id);
        update_field('club', $posel['club'] ?? '', $post_id);
        update_field('district_name', $posel['districtName'] ?? '', $post_id);
        update_field('district_num', $posel['districtNum'] ?? '', $post_id);
        update_field('education_level', $posel['educationLevel'] ?? '', $post_id);
        update_field('profession', $posel['profession'] ?? '', $post_id);
        update_field('voivodeship', $posel['voivodeship'] ?? '', $post_id);
        update_field('number_of_votes', $posel['numberOfVotes'] ?? 0, $post_id);
        update_field('photo_url', "https://api.sejm.gov.pl/sejm/term{$term}/MP/{$posel['id']}/photo", $post_id);

        $count++;
    }

    return true;
}



// Add admin submenu page for importing posłowie
function posel_import_admin_menu()
{
    add_submenu_page(
        'edit.php?post_type=posel',
        'Import posłów',
        'Import posłów',
        'manage_options',
        'import-poslowie',
        'posel_import_admin_page'
    );
}
add_action('admin_menu', 'posel_import_admin_menu');


// Process import form submission via admin_post hook
function posel_handle_import()
{
    if (!current_user_can('manage_options')) {
        wp_die('Brak uprawnień.');
    }

    check_admin_referer('posel_import_action', 'posel_import_nonce');

    $success = import_poslow_from_api(10);

    $redirect_url = add_query_arg('posel_import_result', $success ? 'success' : 'fail', wp_get_referer());
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_posel_import', 'posel_handle_import');

// Process limited import form submission via admin_post hook
function posel_handle_import_test()
{
    if (!current_user_can('manage_options')) {
        wp_die('Brak uprawnień.');
    }

    check_admin_referer('posel_import_test_action', 'posel_import_test_nonce');

    $success = import_poslow_from_api(10, 50); // Import only 50

    $redirect_url = add_query_arg('posel_import_result', $success ? 'test_success' : 'test_fail', wp_get_referer());
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_posel_import_test', 'posel_handle_import_test');


// Import page content
function posel_import_admin_page()
{
    $result = isset($_GET['posel_import_result']) ? sanitize_text_field($_GET['posel_import_result']) : '';

?>
    <div class="wrap">
        <h1>Import posłów</h1>

        <?php if ($result === 'success') : ?>
            <div class="notice notice-success is-dismissible">
                <p>Import zakończony sukcesem.</p>
            </div>
        <?php elseif ($result === 'fail') : ?>
            <div class="notice notice-error is-dismissible">
                <p>Import nie powiódł się.</p>
            </div>
        <?php elseif ($result === 'test_success') : ?>
            <div class="notice notice-success is-dismissible">
                <p>Testowy import 10 posłów zakończony sukcesem.</p>
            </div>
        <?php elseif ($result === 'test_fail') : ?>
            <div class="notice notice-error is-dismissible">
                <p>Testowy import nie powiódł się.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 1em;">
            <?php wp_nonce_field('posel_import_action', 'posel_import_nonce'); ?>
            <input type="hidden" name="action" value="posel_import">
            <input type="submit" class="button button-primary" value="Importuj posłów z API">
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('posel_import_test_action', 'posel_import_test_nonce'); ?>
            <input type="hidden" name="action" value="posel_import_test">
            <input type="submit" class="button" value="TEST: Importuj pierwsze 50 posłów">
        </form>

        <h2>UWAGA Skrypt importuje dane posłów i zapisuje adres enpointu do ich zdjęcia</h2>
        <p>Pobieranie zdjęć i ustawienie ich jako miniaturkę można wykonać poniższym przyciskiem</p>
        <p>Zalecam wykonanie importu testowego na początek przed pełnym importem</p>

        <div>
            <p>Import zdjęć odbywa się w rundach po 5 (logi w konsolce)</p>
            <button id="start-image-import" class="button button-secondary" style="margin-top: 20px;">Importuj zdjęcia posłów</button>
            <div id="image-import-status" style="margin-top: 10px; font-weight: bold;"></div>
        </div>

    </div>
<?php
}


// Localize script with AJAX URL and nonce
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'posel_page_import-poslowie') {
        wp_enqueue_script(
            'posel-image-import',
            get_stylesheet_directory_uri() . '/assets/js/posel-image-import.js',
            ['jquery'],
            null,
            true
        );
        wp_localize_script('posel-image-import', 'poselImportData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('posel_image_import_nonce'),
        ]);
    }
});

// Add AJAX PHP handler for image importing
add_action('wp_ajax_posel_image_import', function () {
    check_ajax_referer('posel_image_import_nonce', 'nonce');

    $offset = intval($_POST['offset'] ?? 0);
    $limit = intval($_POST['limit'] ?? 5);

    $query = new WP_Query([
        'post_type' => 'posel',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'meta_query' => [
            [
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key' => 'image_imported',
                'compare' => 'NOT EXISTS'
            ]
        ],
        'fields' => 'ids',
        'post_status' => 'publish',
    ]);

    if (!$query->have_posts()) {
        wp_send_json_success(['done' => true]);
    }

    $photo_urls = [];
    $image_ids = [];

    foreach ($query->posts as $post_id) {
        $photo_url = get_field('photo_url', $post_id);
        if (!$photo_url) {
            continue;
        }
        array_push($photo_urls, $photo_url);

        $image_id = media_sideload_image_as_attachment($photo_url, $post_id);
        if ($image_id) {
            set_post_thumbnail($post_id, $image_id);
            update_post_meta($post_id, 'image_imported', 1);
        }
        array_push($image_ids, $image_id);
    }

    wp_send_json_success([
        'done' => false,
        'next_offset' => $offset + $limit,
        'processed' => count($query->posts),
        'phot_urls' => $photo_urls,
        'image_ids' => $image_ids
    ]);
});

// Utility to sideload image and return attachment ID
// Had to use curl insted of simply download_url()
// Definitly not an elegant solution but it works...
function media_sideload_image_as_attachment($url, $post_id)
{
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: image/jpeg,image/png,image/webp']); // accept common image types even tho documentation states that it only returns jpeg
    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return new WP_Error('curl_error', $error);
    }

    // Separate headers and body
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    // Get Content-Type header
    preg_match('/Content-Type:\s*(.*?)\s*[\r\n]/i', $headers, $matches);
    $content_type = $matches[1] ?? 'application/octet-stream';

    curl_close($ch);

    // Map Content-Type to extension (documentation states that it only return jpg but just to be sure)
    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $ext = $mime_map[$content_type] ?? 'jpg'; // default to jpg if unknown

    // Construct filename with extension
    $filename = 'posel_photo.' . $ext;

    // Save the file using wp_upload_bits
    $upload = wp_upload_bits($filename, null, $body);

    if ($upload['error']) {
        return new WP_Error('upload_error', $upload['error']);
    }

    // Prepare file array for sideload
    $file_array = [
        'name'     => $filename,
        'tmp_name' => $upload['file'],
    ];

    // Sideload as attachment
    $id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($id)) {
        @unlink($upload['file']);
        return $id;
    }

    return $id;
}
