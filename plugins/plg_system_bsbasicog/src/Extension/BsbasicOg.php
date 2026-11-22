<?php

/**
 * @package     Puffermedia.Plugin
 * @subpackage  System.bsbasicog
 *
 * @copyright   Copyright (C) 2026. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Puffermedia\Plugin\System\BsbasicOg\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Event\Plugin\System\Schemaorg\BeforeCompileHeadEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

/**
 * Adds an "Open Graph" tab to the article edit form and outputs the
 * per-article Open Graph tags on the article page.
 *
 * On article pages it also outputs the article's dates: og:type "article"
 * with article:published_time / article:modified_time, and datePublished /
 * dateModified on the article's schema.org JSON-LD node (when the System -
 * Schema.org plugin is enabled).
 *
 * It also adds a "Bootstrap" tab to site menu items: Bootstrap JS components
 * ticked there are loaded by the puffermedia-bsbasic template on that menu
 * item's pages only (the template reads the value; this plugin only adds the field).
 *
 * Values live in the article's own params (the `attribs` column) and the menu
 * item's `params`, so no database tables are added.
 */
final class BsbasicOg extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	protected $autoloadLanguage = true;

	/**
	 * Article row (attribs and dates) for the current request, keyed by id.
	 *
	 * @var array<int, object|null>
	 */
	private array $articles = [];

	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepareForm'      => 'onContentPrepareForm',
			'onBeforeCompileHead'       => 'onBeforeCompileHead',
			// LOW: after core's content plugin has added the automatic
			// Article node and after Schemaorg - BlogPosting has formatted
			// an editor's own dates.
			'onSchemaBeforeCompileHead' => ['onSchemaBeforeCompileHead', Priority::LOW],
		];
	}

	/**
	 * Adds the Open Graph fieldset (shown as a tab) to the admin article form, and
	 * the Bootstrap fieldset to site menu items.
	 */
	public function onContentPrepareForm(PrepareFormEvent $event): void
	{
		$form = $event->getForm();
		$name = $form->getName();

		if ($name === 'com_content.article') {
			$form->loadFile(JPATH_PLUGINS . '/system/bsbasicog/forms/article.xml', false);

			return;
		}

		// Administrator menu items (client_id 1) have no front-end page to load scripts on.
		if ($name === 'com_menus.item' && $this->menuItemClientId($event->getData()) === 0) {
			$form->loadFile(JPATH_PLUGINS . '/system/bsbasicog/forms/menuitem.xml', false);
		}
	}

	/**
	 * Client id of the menu item being edited: from the form data, else the
	 * Site/Administrator filter of the Menus: Items list it was opened from.
	 */
	private function menuItemClientId(mixed $data): int
	{
		if (\is_object($data) && isset($data->client_id)) {
			return (int) $data->client_id;
		}

		if (\is_array($data) && isset($data['client_id'])) {
			return (int) $data['client_id'];
		}

		return (int) $this->getApplication()->getUserState('com_menus.items.client_id', 0);
	}

	/**
	 * On a single-article page, overrides the Open Graph tags with the values
	 * entered in the article's Open Graph tab, and adds the article type and
	 * dates. Empty fields change nothing, so the template's defaults (or
	 * Joomla's own meta description) still apply.
	 *
	 * This runs while the head is rendered, after the template has set its
	 * defaults, so setMetaData() here replaces them.
	 */
	public function onBeforeCompileHead(): void
	{
		$app = $this->getApplication();

		if (!$app->isClient('site')) {
			return;
		}

		$input = $app->getInput();

		if ($input->getCmd('option') !== 'com_content' || $input->getCmd('view') !== 'article') {
			return;
		}

		$article = $this->loadArticle($input->getInt('id'));

		if ($article === null) {
			return;
		}

		$attribs = new Registry((string) $article->attribs);
		$doc     = $app->getDocument();

		$title = trim((string) $attribs->get('bsbasic_og_title', ''));
		$desc  = trim((string) $attribs->get('bsbasic_og_description', ''));
		$image = trim((string) $attribs->get('bsbasic_og_image', ''));

		if ($title !== '') {
			$doc->setMetaData('og:title', $title, 'property');
		}

		if ($desc !== '') {
			$doc->setMetaData('og:description', $desc, 'property');
		}

		if ($image !== '') {
			// The media field stores "path#joomlaImage://..."; keep the path only.
			$path = HTMLHelper::_('cleanImageURL', $image)->url;

			if (!preg_match('#^https?://#i', $path)) {
				$path = Uri::root() . ltrim($path, '/');
			}

			$doc->setMetaData('og:image', $path, 'property');
		}

		[$published, $modified] = $this->articleDates($article);

		$doc->setMetaData('og:type', 'article', 'property');
		$doc->setMetaData('article:published_time', $published, 'property');
		$doc->setMetaData('article:modified_time', $modified, 'property');
	}

	/**
	 * Adds datePublished and dateModified to the article's own JSON-LD node:
	 * core's automatic Article node (which only has dateCreated, and only
	 * when Show Publish Date is on) or a type chosen in the article's Schema
	 * tab such as BlogPosting. Dates an editor entered there are kept.
	 */
	public function onSchemaBeforeCompileHead(BeforeCompileHeadEvent $event): void
	{
		if (!$this->getApplication()->isClient('site')) {
			return;
		}

		$parts = explode('.', $event->getContext());

		if (\count($parts) !== 3 || $parts[0] !== 'com_content' || $parts[1] !== 'article') {
			return;
		}

		$article = $this->loadArticle((int) $parts[2]);

		if ($article === null) {
			return;
		}

		$schema = $event->getSchema();
		$graph  = $schema->get('@graph');

		if (!\is_array($graph)) {
			return;
		}

		$nodeId                 = Uri::root() . '#/schema/com_content/article/' . (int) $article->id;
		[$published, $modified] = $this->articleDates($article);
		$changed                = false;

		foreach ($graph as &$node) {
			// Registry::get() can hand back nodes as objects.
			$node = (array) $node;

			if (($node['@id'] ?? '') !== $nodeId) {
				continue;
			}

			if (empty($node['datePublished'])) {
				$node['datePublished'] = $published;
				$changed               = true;
			}

			if (empty($node['dateModified'])) {
				$node['dateModified'] = $modified;
				$changed              = true;
			}
		}

		unset($node);

		if ($changed) {
			$schema->set('@graph', $graph);
		}
	}

	/**
	 * Loads the article's attribs and dates once per request.
	 */
	private function loadArticle(int $id): ?object
	{
		if ($id <= 0) {
			return null;
		}

		if (!\array_key_exists($id, $this->articles)) {
			$db    = $this->getDatabase();
			$query = $db->getQuery(true)
				->select($db->quoteName(['id', 'attribs', 'created', 'modified', 'publish_up']))
				->from($db->quoteName('#__content'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$this->articles[$id] = $db->setQuery($query)->loadObject() ?: null;
		}

		return $this->articles[$id];
	}

	/**
	 * Published date (Start Publishing, else Created) and modified date
	 * (Modified, else the published date), as ISO 8601 in UTC.
	 *
	 * @return  array{0: string, 1: string}
	 */
	private function articleDates(object $article): array
	{
		$published = $this->isDate($article->publish_up) ? $article->publish_up : $article->created;
		$modified  = $this->isDate($article->modified) ? $article->modified : $published;

		return [
			Factory::getDate($published)->toISO8601(),
			Factory::getDate($modified)->toISO8601(),
		];
	}

	private function isDate(?string $value): bool
	{
		return $value !== null && $value !== '' && strpos($value, '0000-00-00') !== 0;
	}
}
