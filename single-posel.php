<?php get_header(); ?>

<main>
    <article class="posel-article">
        <div class="posel-photo">
            <?php
            if (has_post_thumbnail()) {
                the_post_thumbnail('medium');
            } else {
                $fallback = get_field('photo_url');
                if ($fallback) {
                    echo '<img src="' . esc_url($fallback) . '" alt="' . esc_attr(get_the_title()) . '">';
                }
            }
            ?>
        </div>
        <div class="posel-details">
            <h1><?php the_title(); ?></h1>
            <ul>
                <li><strong>Imię:</strong> <?php echo esc_html(get_field('first_name')); ?></li>
                <li><strong>Drugie imię:</strong> <?php echo esc_html(get_field('second_name')); ?></li>
                <li><strong>Nazwisko:</strong> <?php echo esc_html(get_field('last_name')); ?></li>
                <li><strong>Email:</strong> <a href="mailto:<?php echo esc_attr(get_field('email')); ?>"><?php echo esc_html(get_field('email')); ?></a></li>
                <li><strong>Data urodzenia:</strong> <?php echo esc_html(get_field('birth_date')); ?></li>
                <li><strong>Miejsce urodzenia:</strong> <?php echo esc_html(get_field('birth_location')); ?></li>
                <li><strong>Klub:</strong> <?php echo esc_html(get_field('club')); ?></li>
                <li><strong>Okręg wyborczy (nazwa):</strong> <?php echo esc_html(get_field('district_name')); ?></li>
                <li><strong>Okręg wyborczy (numer):</strong> <?php echo esc_html(get_field('district_num')); ?></li>
                <li><strong>Poziom wykształcenia:</strong> <?php echo esc_html(get_field('education_level')); ?></li>
                <li><strong>Zawód:</strong> <?php echo esc_html(get_field('profession')); ?></li>
                <li><strong>Województwo:</strong> <?php echo esc_html(get_field('voivodeship')); ?></li>
                <li><strong>Liczba głosów:</strong> <?php echo esc_html(get_field('number_of_votes')); ?></li>
            </ul>
        </div>
    </article>
</main>

<?php get_footer(); ?>