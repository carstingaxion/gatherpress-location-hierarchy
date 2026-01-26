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
			'linkColor' => array(
				'type' => 'string',
				'default' => ''
			),
			'showVenue' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'html' => false,
			'align' => true,
			'color' => array(
				'link' => true,
				'text' => true,
				'background' => true
			)
		),
		'textdomain' => 'gatherpress-venue-hierarchy',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
