/**
 * Dynamically represent changes to the post title in ACF options
 */
import jQuery from 'jquery';
const { subscribe } = wp.data;

const $ = jQuery.noConflict();
$(() => {
	if (!window.acfWatchChanges) {
		return;
	}

	const progamCPTACFWatch = window.acfWatchChanges({
		postTypeTest: (postType) =>
			['format', 'program'].includes(postType.slug),
		group: '#acf-group_61240bc1afe31',
		acfFields: {
			'#acf-field_612ec3547ecb5': {
				key: 'title-hidden',
				type: 'checkbox',
				acf: '#acf-field_612ec3547ecb5',
				action: (checked) => {
					if (!checked) {
						window.acfWatchChanges
							.getBasicContext()
							.addClass('has-hidden-post-title');
						window.wp.data
							.dispatch('core/notices')
							.createNotice('info', '🤫 The title is hidden', {
								isDismissible: false,
								id: 'has-hidden-post-title',
							});
						return;
					}
					window.wp.data
						.dispatch('core/notices')
						.removeNotice('has-hidden-post-title');
					window.acfWatchChanges
						.getBasicContext()
						.removeClass('has-hidden-post-title');
				},
			},
			'acf[field_612d7a1f9df36][field_612d7a3c9df37]': {
				key: 'background_color',
				type: 'string',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612d7a3c9df37]"]',
				action: 'setCssVar',
				triggersHasBackground: true,
				contrastCheck: {
					against: 'acf[field_612d7a1f9df36][field_612d7b869df39]',
					position: 'background',
					computeFrom: '.editor-styles-wrapper',
					computeAttribute: 'backgroundColor',
				},
			},
			'acf[field_612d7a1f9df36][field_612d7b869df39]': {
				key: 'title_color',
				type: 'string',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612d7b869df39]"]',
				action: 'setCssVar',
				contrastCheck: {
					against: 'acf[field_612d7a1f9df36][field_612d7a3c9df37]',
					position: 'foreground',
					computeFrom: '.edit-post-visual-editor__post-title-wrapper',
					computeAttribute: 'color',
				},
			},
			'acf[field_612d7a1f9df36][field_612e628acf79d]': {
				key: 'title_shadow_opacity',
				type: 'float',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612e628acf79d]"]',
				action: 'setCssVar',
			},
			'acf[field_612d7a1f9df36][field_612d7b369df38]': {
				key: 'background_image',
				type: 'file',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612d7b369df38]"]',
				action: 'setCssVar',
				triggersHasBackground: true,
			},
			'acf[field_612d7a1f9df36][field_612e8f89ed814]': {
				key: 'background_size',
				type: 'string',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612e8f89ed814]"]',
				action: 'setCssVar',
			},
			'acf[field_612d7a1f9df36][field_612e8e53ed810]': {
				key: 'background_position',
				type: 'string',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612e8e53ed810]"]',
				action: 'setCssVar',
			},
			'acf[field_612d7a1f9df36][field_612e8f08ed813]': {
				key: 'background_repeat',
				type: 'string',
				acf: 'input[name="acf[field_612d7a1f9df36][field_612e8f08ed813]"]',
				action: 'setCssVar',
			},
		},
	});

	const setupWait = subscribe(() => {
		if (!wp?.data?.select) return;
		const currentPostType = wp.data
			.select('core/editor')
			.getCurrentPostType();
		if (!currentPostType) return;
		const postType = wp.data.select('core').getPostType(currentPostType);
		if (!postType) return;

		if (!document.querySelector('#acf-group_61240bc1afe31')) {
			return;
		}

		if (!document.querySelector('#cmls-acf_post_title')) {
			return;
		}

		if (['program', 'format'].includes(postType.slug)) {
			// ACF display option styles
			const editorCSS = require('./acf-title.css?raw');
			if (!document.querySelector('#cmls-program-cpt-acf_post_title')) {
				window.acfWatchChanges.$inContext('#cmls-acf_post_title')
					.after(`
						<style id="cmls-program-cpt-acf_post_title">${editorCSS}</style>
					`);
			}
		}
		setupWait();
	});
});
