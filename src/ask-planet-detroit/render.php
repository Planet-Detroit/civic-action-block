<?php
/**
 * Server-side rendering for the Ask Planet Detroit block.
 * Renders the reader question form with inline styles and submission JS.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (empty for dynamic blocks).
 * @var WP_Block $block      Block instance.
 */

$form_title    = esc_html( $attributes['formTitle'] ?? 'Got a question about this?' );
$form_subtitle = esc_html( $attributes['formSubtitle'] ?? '' );
$wrapper_attributes = get_block_wrapper_attributes();

// Unique ID so multiple blocks on one page don't collide
$uid = 'ask-pd-' . wp_unique_id();
?>

<div <?php echo $wrapper_attributes; ?>>
	<div id="<?php echo esc_attr( $uid ); ?>" style="background: #fff8e8; border: 1px solid #f0dca0; border-radius: 8px; padding: 16px; font-family: -apple-system, sans-serif; max-width: 400px;">
		<?php if ( ! empty( $form_title ) ) : ?>
			<p style="font-size: 15px; font-weight: 700; color: #333; margin: 0 0 4px 0;"><?php echo $form_title; ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $form_subtitle ) ) : ?>
			<p style="font-size: 12px; color: #666; margin: 0 0 10px 0;"><?php echo $form_subtitle; ?></p>
		<?php endif; ?>

		<form id="<?php echo esc_attr( $uid ); ?>-form">
			<textarea
				id="<?php echo esc_attr( $uid ); ?>-question"
				placeholder="What would you like to know?"
				required
				maxlength="2000"
				style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 60px; margin-bottom: 6px;"
			></textarea>

			<!-- Honeypot: hidden from humans, bots fill it -->
			<input type="text" name="website" id="<?php echo esc_attr( $uid ); ?>-website" style="position: absolute; left: -9999px; opacity: 0; height: 0;" tabindex="-1" autocomplete="off">

			<div style="display: flex; gap: 6px; margin-bottom: 6px;">
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-name" placeholder="Name (optional)" style="flex: 1; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;">
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-zip" placeholder="Zip (optional)" style="width: 80px; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;">
			</div>
			<div style="display: flex; gap: 8px;">
				<input type="email" id="<?php echo esc_attr( $uid ); ?>-email" placeholder="Email (optional)" style="flex: 1; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;">
				<button type="submit" style="padding: 6px 14px; background: #ea5a39; color: white; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: 600;">Ask</button>
			</div>
		</form>

		<div id="<?php echo esc_attr( $uid ); ?>-result" style="display: none;"></div>
	</div>
</div>

<script>
(function() {
	var uid = <?php echo wp_json_encode( $uid ); ?>;
	var form = document.getElementById(uid + '-form');
	var wrapper = document.getElementById(uid);
	var resultDiv = document.getElementById(uid + '-result');
	if (!form) return;

	form.addEventListener('submit', function(e) {
		e.preventDefault();
		var question = document.getElementById(uid + '-question').value.trim();
		var hp = document.getElementById(uid + '-website').value;
		if (!question) return;

		var payload = {
			question: question,
			article_url: window.location.href,
			article_title: document.title
		};

		var nameEl = document.getElementById(uid + '-name');
		var emailEl = document.getElementById(uid + '-email');
		var zipEl = document.getElementById(uid + '-zip');
		if (nameEl && nameEl.value.trim()) payload.name = nameEl.value.trim();
		if (emailEl && emailEl.value.trim()) payload.email = emailEl.value.trim();
		if (zipEl && zipEl.value.trim()) payload.zip_code = zipEl.value.trim();
		if (hp) payload.website = hp;

		var btn = form.querySelector('button[type="submit"]');
		if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }

		fetch('https://ask-planet-detroit-production.up.railway.app/api/reader-questions', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		}).then(function(r) { return r.json(); }).then(function(data) {
			form.style.display = 'none';
			var html = '<p style="font-size: 14px; color: #2f80c3; font-weight: 600; margin: 8px 0;">Thank you! A reporter will review your question.</p>';
			if (data.related_articles && data.related_articles.length > 0) {
				html += '<p style="font-size: 13px; font-weight: 600; color: #333; margin: 8px 0 4px 0;">Related coverage:</p>';
				html += '<ul style="margin: 0; padding-left: 16px; font-size: 13px;">';
				data.related_articles.forEach(function(a) {
					html += '<li style="margin-bottom: 4px;"><a href="' + a.article_url + '" style="color: #2f80c3;">' + a.article_title + '</a></li>';
				});
				html += '</ul>';
			}
			resultDiv.innerHTML = html;
			resultDiv.style.display = 'block';
		}).catch(function() {
			form.style.display = 'none';
			resultDiv.innerHTML = '<p style="font-size: 14px; color: #2f80c3; font-weight: 600; margin: 8px 0;">Thank you! A reporter will review your question.</p>';
			resultDiv.style.display = 'block';
		});
	});
})();
</script>
