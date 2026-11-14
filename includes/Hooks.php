<?php
/**
 * Hooks for PageNotice extension
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\PageNotice;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Article;
use MediaWiki\Page\Hook\ArticleViewFooterHook;
use MediaWiki\Page\Hook\ArticleViewHeaderHook;
use MediaWiki\Page\Hook\ImageOpenShowImageInlineBeforeHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Parser\ParserOptions;
use Wikimedia\Assert\Assert;

class Hooks implements ArticleViewHeaderHook, ArticleViewFooterHook, ImageOpenShowImageInlineBeforeHook {

	private ?Parser $parser = null;

	public function __construct(
		private readonly ParserFactory $parserFactory,
	) {
	}

	private function addNotice( IContextSource $context, string $position ): void {
		Assert::parameter(
			in_array( $position, [ 'top', 'bottom' ], true ),
			'position',
			'must be "top" or "bottom"',
		);

		$out = $context->getOutput();
		$title = $context->getTitle();
		$name = $title->getPrefixedDBKey();
		$ns = $title->getNamespace();

		if ( !$context->getConfig()->get( 'PageNoticeDisablePerPageNotices' ) ) {
			// Messages:
			// * top-notice-<page name with ucfirst and spaces to underscores>
			// * bottom-notice-<page name with ucfirst and spaces to underscores>
			$header = $context->msg( "$position-notice-$name" );
			if ( !$header->isBlank() ) {
				$this->wrapPageNotice( $out, $header->plain(), "ext-pagenotice-$position-notice",
					"$position-notice", $position );
			}
		}

		// Messages:
		// * top-notice-ns-<namespace id>
		// * bottom-notice-ns-<namespace id>
		$nsheader = $context->msg( "$position-notice-ns-$ns" );
		if ( !$nsheader->isBlank() ) {
			$this->wrapPageNotice( $out, $nsheader->plain(), "ext-pagenotice-$position-notice-ns",
				"$position-notice-ns", $position );
		}

		// Messages:
		// * top-notice-global
		// * bottom-notice-global
		$globalheader = $context->msg( "$position-notice-global" );
		if ( !$globalheader->isBlank() ) {
			// The ID for the inner <div> wrapper is intentionally not provided here as the previous ones
			// were (mostly) for backwards compatibility
			$this->wrapPageNotice( $out, $globalheader->plain(), "ext-pagenotice-$position-notice-global",
				null, $position );
		}
	}

	/**
	 * Wraps the given page notice in a <div> with the given class.
	 * Optionally adds an inner wrapper with an ID, used for backwards compatibility.
	 *
	 * @param OutputPage $out The output page to add the notice to
	 * @param string $wikitext The wikitext of the notice
	 * @param string $wrapperClass The class to add to the outer <div>
	 * @param ?string $innerWrapperId The ID to add to the inner <div> wrapper, or null if not needed
	 */
	private function wrapPageNotice(
		OutputPage $out, string $wikitext, string $wrapperClass, ?string $innerWrapperId,
		string $position = 'top'
	): void {
		$this->parser ??= $this->parserFactory->create();
		// workaround for T392226 to properly parse tables
		$this->parser->startExternalParse( $out->getTitle(), ParserOptions::newFromContext( $out->getContext() ),
			Parser::OT_HTML );

		$result = $this->parser->recursiveTagParseFully( $wikitext );
		// remove outer <p> if present to avoid unwanted margins around the notice
		$result = Parser::stripOuterParagraph( $result );

		// [THBWiki F-27] 只继承页顶指示器，不把提示页面的分类/链接等元数据并进目标页面。
		// 上游用 addParserOutputMetadata()，会把 MediaWiki:Top-notice-ns-6 里的
		// [[分类:头部模板]] 也加到文件页（生产 1.39 不会）。
		$out->setIndicators( $this->parser->getOutput()->getIndicators() );
		if ( $innerWrapperId !== null ) {
			// add inner wrapper with an ID for backwards compatibility
			$result = Html::rawElement( 'div', [ 'id' => $innerWrapperId ], $result );
		}
		$html = Html::rawElement( 'div', [ 'class' => $wrapperClass ], $result );
		if ( $position === 'top' ) {
			// [THBWiki F-27] 顶部提示要插到正文最前（含文件页 #mw-imagepage-content 之前），
			// 上游的 addHTML() 在文件页会落到图片区下方，缺失文件页则会跑到页尾。
			$out->prependHTML( $html );
		} else {
			$out->addHTML( $html );
		}
	}

	/**
	 * Renders relevant header notices for the current page.
	 * @param Article $article
	 * @param bool &$outputDone
	 * @param bool &$pcache
	 */
	public function onArticleViewHeader( $article, &$outputDone, &$pcache ) {
		// [THBWiki F-27] 文件页改走 ImageOpenShowImageInlineBefore：
		// 1.46 的文件页若没有说明页（missing/空），ArticleViewHeader 不会触发，提示会整个丢失；
		// 有说明页时该钩子也会把提示插到 #mw-imagepage-content 里面（图片区下方）。
		if ( $article->getTitle()->getNamespace() === NS_FILE ) {
			return;
		}
		$this->addNotice( $article->getContext(), 'top' );
	}

	/**
	 * Renders relevant footer notices for the current page.
	 * @param Article $article
	 * @param bool $patrolFooterShown
	 */
	public function onArticleViewFooter( $article, $patrolFooterShown ) {
		if ( $article->getTitle()->getNamespace() === NS_FILE ) {
			return;
		}
		$this->addNotice( $article->getContext(), 'bottom' );
	}

	/**
	 * 文件页的顶部/底部提示：在图片输出之前 prepend 到正文开头，
	 * 与 1.39 的 ImageOpenShowImageInlineBefore 行为一致。
	 * @param \MediaWiki\Page\ImagePage $imagePage
	 * @param OutputPage $output
	 */
	public function onImageOpenShowImageInlineBefore( $imagePage, $output ) {
		$context = $imagePage->getContext();
		$this->addNotice( $context, 'top' );
		$this->addNotice( $context, 'bottom' );
	}
}
