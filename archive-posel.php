<?php get_header(); ?>

<main>
    <h1>Lista posłów</h1>

    <?php
    $paged = max(1, get_query_var('paged'), get_query_var('page'));

    $args = [
        'post_type' => 'posel',
        'posts_per_page' => 25,
        'paged' => $paged,
        'orderby' => 'meta_value',
        'meta_key' => 'last_name',
        'order' => 'ASC',
    ];

    $poslowie_query = new WP_Query($args);

    if ($poslowie_query->have_posts()) : ?>
        <ul class="posel-grid">
            <?php while ($poslowie_query->have_posts()) : $poslowie_query->the_post(); ?>
                <li class="posel-item">
                    <a href="<?php the_permalink(); ?>">
                        <img src="<?php echo esc_url(get_field('photo_url')); ?>" alt="<?php the_title_attribute(); ?>" />
                    </a><br>
                    <a href="<?php the_permalink(); ?>"><strong><?php the_title(); ?></strong></a><br>
                    <strong>Klub:</strong> <?php echo esc_html(get_field('club')); ?><br>
                    <strong>Okręg:</strong> <?php echo esc_html(get_field('district_name')); ?>
                </li>
            <?php endwhile; ?>
        </ul>

        <nav class="pagination">
            <?php
            echo paginate_links([
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $poslowie_query->max_num_pages,
                'prev_text' => '&laquo; Poprzednia',
                'next_text' => 'Następna &raquo;',
            ]);
            ?>
        </nav>

        <?php wp_reset_postdata(); ?>

    <?php else : ?>
        <p>Brak posłów do wyświetlenia.</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>