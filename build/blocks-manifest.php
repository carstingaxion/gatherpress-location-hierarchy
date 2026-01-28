<?php
// This file is generated. Do not modify it manually.
return array(
	'build' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'telex/block-gatherpress-venue-hierarchy',
		'version' => '0.1.0',
		'title' => 'Location Hierarchy Display',
		'category' => 'widgets',
		'icon' => 'location',
		'description' => 'Displays the complete location hierarchy as inline text',
		'example' => array(
			
		),
		'attributes' => array(
			'startLevel' => array(
				'type' => 'number',
				'default' => 1
			),
			'endLevel' => array(
				'type' => 'number',
				'default' => 999
			),
			'enableLinks' => array(
				'type' => 'boolean',
				'default' => false
			),
			'showVenue' => array(
				'type' => 'boolean',
				'default' => false
			),
			'separator' => array(
				'type' => 'string',
				'default' => ' > '
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'html' => false,
			'color' => array(
				'gradients' => true,
				'link' => true,
				'__experimentalDefaultControls' => array(
					'background' => true,
					'text' => true,
					'link' => true
				)
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'interactivity' => array(
				'clientNavigation' => true
			),
			'__experimentalBorder' => array(
				'radius' => true,
				'color' => true,
				'width' => true,
				'style' => true,
				'__experimentalDefaultControls' => array(
					'radius' => true,
					'color' => true,
					'width' => true,
					'style' => true
				)
			)
		),
		'textdomain' => 'gatherpress-venue-hierarchy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
