<?php
/**
 * Server-side rendering for the Civic Action Box block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

$civic_html = $attributes['civicHtml'] ?? '';

if ( empty( $civic_html ) ) {
	return;
}

$box_title = $attributes['boxTitle'] ?? '';
$wrapper_attributes = get_block_wrapper_attributes();
?>

<div <?php echo $wrapper_attributes; ?>>
	<?php if ( ! empty( $box_title ) ) : ?>
		<h3 class="wp-block-planet-detroit-civic-action__title"><?php echo esc_html( $box_title ); ?></h3>
	<?php endif; ?>
	<div class="wp-block-planet-detroit-civic-action__content">
		<?php
		// The civic HTML comes from the civic-action-builder tool and contains
		// inline-styled content. We allow specific tags and attributes needed
		// for the civic action box rendering.
		echo wp_kses(
			$civic_html,
			array_merge(
				wp_kses_allowed_html( 'post' ),
				array(
					'style' => array(),
					'div'   => array(
						'style' => true,
						'class' => true,
						'id'    => true,
					),
					'span'  => array(
						'style' => true,
						'class' => true,
					),
					'a'     => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
						'style'  => true,
						'class'  => true,
					),
					'p'     => array(
						'style' => true,
						'class' => true,
					),
					'h1'    => array( 'style' => true, 'class' => true ),
					'h2'    => array( 'style' => true, 'class' => true ),
					'h3'    => array( 'style' => true, 'class' => true ),
					'h4'    => array( 'style' => true, 'class' => true ),
					'ul'    => array( 'style' => true, 'class' => true ),
					'ol'    => array( 'style' => true, 'class' => true ),
					'li'    => array( 'style' => true, 'class' => true ),
					'strong' => array( 'style' => true ),
					'em'    => array( 'style' => true ),
					'br'    => array(),
					'hr'    => array( 'style' => true ),
					'img'   => array(
						'src'   => true,
						'alt'   => true,
						'style' => true,
						'class' => true,
						'width' => true,
						'height' => true,
					),
					'svg'   => array(
						'xmlns'   => true,
						'width'   => true,
						'height'  => true,
						'viewbox' => true,
						'fill'    => true,
						'style'   => true,
						'class'   => true,
					),
					'path'  => array(
						'd'    => true,
						'fill' => true,
					),
				)
			)
		);
		?>
	</div>
</div>
