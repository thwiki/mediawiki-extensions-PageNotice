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

class PageNoticeHooks {
	/**
	 * Renders relevant header and footer notices for the current edit page.
	 * @param EditPage $edit
	 * @param OutputPage $out
	 * @return bool
	 */
	static public function renderForEdit( EditPage $editPage, OutputPage $output ) {
		$pageNoticeDisablePerPageNotices = $output->getContext()
			->getConfig()
			->get( 'PageNoticeDisablePerPageNotices' );

		$name = $output->getTitle()->getPrefixedDBKey();
		$ns = $output->getTitle()->getNamespace();

		$key = str_replace( '/', '-', $name );
		$header = $output->msg( "top-notice-$key" );
		$nsheader = $output->msg( "top-notice-ns-$ns" );
		$footer = $output->msg( "bottom-notice-$key" );
		$nsfooter = $output->msg( "bottom-notice-ns-$ns" );

		$needStyles = false;

		if ( !$pageNoticeDisablePerPageNotices && !$header->isBlank() ) {
			$editPage->editFormPageTop = Html::rawElement(
				'div',
				[ 'id' => 'top-notice' ],
				$header->parse()
			) . $editPage->editFormPageTop;
			$needStyles = true;
		}
		if ( !$nsheader->isBlank() ) {
			$editPage->editFormPageTop = Html::rawElement(
				'div',
				[ 'id' => 'top-notice-ns' ],
				$nsheader->parse()
			) . $editPage->editFormPageTop;
			$needStyles = true;
		}
		if ( !$pageNoticeDisablePerPageNotices && !$footer->isBlank() ) {
			$editPage->editFormPageTop = $editPage->editFormPageTop . Html::rawElement(
				'div',
				[ 'id' => 'bottom-notice' ],
				$footer->parse()
			);
			$needStyles = true;
		}
		if ( !$nsfooter->isBlank() ) {
			$editPage->editFormPageTop = $editPage->editFormPageTop . Html::rawElement(
				'div',
				[ 'id' => 'bottom-notice-ns' ],
				$nsfooter->parse()
			);
			$needStyles = true;
		}

		if ( $needStyles ) {
			$output->addModuleStyles( 'ext.pageNotice' );
		}

		return true;
	}

	/**
	 * Renders relevant header and footer notices for the current file info page.
	 * @param ImagePage $imagepage
	 * @param OutputPage $output
	 * @return bool
	 */
	static public function renderForImage( ImagePage $imagepage, OutputPage $output ) {
		$pageNoticeDisablePerPageNotices = $output->getContext()
			->getConfig()
			->get( 'PageNoticeDisablePerPageNotices' );

		$name = $output->getTitle()->getPrefixedDBKey();
		$ns = $output->getTitle()->getNamespace();
		if ($ns !== NS_FILE || Action::getActionName( $output->getContext() ) == 'edit') return true;

		$key = str_replace( '/', '-', $name );
		$header = $output->msg( "top-notice-$key" );
		$nsheader = $output->msg( "top-notice-ns-$ns" );
		$footer = $output->msg( "bottom-notice-$key" );
		$nsfooter = $output->msg( "bottom-notice-ns-$ns" );

		$needStyles = false;

		if ( !$pageNoticeDisablePerPageNotices && !$header->isBlank() ) {
			$output->prependHTML(
				Html::rawElement(
					'div',
					[ 'id' => 'top-notice' ],
					$header->parse()
				)
			);
			$needStyles = true;
		}
		if ( !$nsheader->isBlank() ) {
			$output->prependHTML(
				Html::rawElement(
					'div',
					[ 'id' => 'top-notice-ns' ],
					$nsheader->parse()
				)
			);
			$needStyles = true;
		}
		if ( !$pageNoticeDisablePerPageNotices && !$footer->isBlank() ) {
			$output->addHTML(
				Html::rawElement(
					'div',
					[ 'id' => 'bottom-notice' ],
					$footer->parse()
				)
			);
			$needStyles = true;
		}
		if ( !$nsfooter->isBlank() ) {
			$output->addHTML(
				Html::rawElement(
					'div',
					[ 'id' => 'bottom-notice-ns' ],
					$nsfooter->parse()
				)
			);
			$needStyles = true;
		}

		if ( $needStyles ) {
			$output->addModuleStyles( 'ext.pageNotice' );
		}

		return true;
	}

	/**
	 * Renders relevant header and footer notices for the current file info page.
	 * @param OutputPage $output
	 * @param string $text
	 * @return bool
	 */
	static public function renderForPage( OutputPage &$output, string &$text ) {
		$pageNoticeDisablePerPageNotices = $output->getContext()
			->getConfig()
			->get( 'PageNoticeDisablePerPageNotices' );

		$name = $output->getTitle()->getPrefixedDBKey();
		$ns = $output->getTitle()->getNamespace();
		if ( $ns === NS_FILE || ( is_null( $output->getRevisionId() ) && !$output->isArticle() ) ) return true;

		$key = str_replace( '/', '-', $name );
		$header = $output->msg( "top-notice-$key" );
		$nsheader = $output->msg( "top-notice-ns-$ns" );
		$footer = $output->msg( "bottom-notice-$key" );
		$nsfooter = $output->msg( "bottom-notice-ns-$ns" );

		$needStyles = false;

		if ( !$pageNoticeDisablePerPageNotices && !$header->isBlank() ) {
			$text = Html::rawElement(
				'div',
				[ 'id' => 'top-notice' ],
				$header->parse()
			) . $text;
			$needStyles = true;
		}
		if ( !$nsheader->isBlank() ) {
			$text = Html::rawElement(
				'div',
				[ 'id' => 'top-notice-ns' ],
				$nsheader->parse()
			) . $text;
			$needStyles = true;
		}
		if ( !$pageNoticeDisablePerPageNotices && !$footer->isBlank() ) {
			$text = $text . Html::rawElement(
				'div',
				[ 'id' => 'bottom-notice' ],
				$footer->parse()
			);
			$needStyles = true;
		}
		if ( !$nsfooter->isBlank() ) {
			$text = $text . Html::rawElement(
				'div',
				[ 'id' => 'bottom-notice-ns' ],
				$nsfooter->parse()
			);
			$needStyles = true;
		}

		if ( $needStyles ) {
			$output->addModuleStyles( 'ext.pageNotice' );
		}

		return true;
	}
}
