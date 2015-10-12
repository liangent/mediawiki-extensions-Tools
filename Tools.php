<?php
/**
 * Tools extension - the thing that needs you.
 *
 * For more info see http://mediawiki.org/wiki/Extension:Tools
 *
 * @file
 * @ingroup Extensions
 * @author Liangent, 2014
 * @license GNU General Public Licence 2.0 or later
 */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Tools',
	'author' => array(
		'Liangent',
	),
	'version'  => '0.2.0',
	// 'url' => 'https://www.mediawiki.org/wiki/Extension:Tools',
	'descriptionmsg' => 'tools-desc',
);

/* Setup */

// Register files
$wgAutoloadClasses['ToolsHooks'] = __DIR__ . '/Tools.hooks.php';
$wgAutoloadClasses['SpecialVariantTroubleshooting'] = __DIR__ . '/specials/SpecialVariantTroubleshooting.php';
$wgAutoloadClasses['SpecialCompareArticleVariants'] = __DIR__ . '/specials/SpecialCompareArticleVariants.php';
$wgAutoloadClasses['TemplateDuplicateArgumentsPage'] = __DIR__ . '/specials/SpecialTemplateDuplicateArguments.php';
$wgMessagesDirs['Tools'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['ToolsAlias'] = __DIR__ . '/Tools.i18n.alias.php';

// Register hooks
$wgHooks['wgQueryPages'][] = 'ToolsHooks::onwgQueryPages';

// Register special pages
$wgSpecialPages['VariantTroubleshooting'] = 'SpecialVariantTroubleshooting';
$wgSpecialPageGroups['VariantTroubleshooting'] = 'other';
$wgSpecialPages['CompareArticleVariants'] = 'SpecialCompareArticleVariants';
$wgSpecialPageGroups['CompareArticleVariants'] = 'wiki';
$wgSpecialPages['TemplateDuplicateArguments'] = 'TemplateDuplicateArgumentsPage';
$wgSpecialPageGroups['TemplateDuplicateArguments'] = 'maintenance';

// Register modules
$wgResourceModules['ext.Tools.foo'] = array(
	'scripts' => array(
		'modules/ext.Tools.foo.js',
	),
	'styles' => array(
		'modules/ext.Tools.foo.css',
	),
	'messages' => array(
	),
	'dependencies' => array(
	),

	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Tools',
);


/* Configuration */

// Enable Foo
#$wgToolsEnableFoo = true;
