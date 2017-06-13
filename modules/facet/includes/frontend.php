<?php

echo '<div class="facetwp-bb-mpodule">';
if ( ! empty( $settings->title ) ) {
	echo '<h4 class="facettp-facet-title">' . esc_html( $settings->title ) . '</h4>';
}
echo facetwp_display( 'facet', $settings->facet );

echo '</div>';
