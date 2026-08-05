<?php

namespace CartRelay\Tests\Unit;

use CartRelay\App\Components\Forms\Form;
use PHPUnit\Framework\TestCase;

class FormTabsTest extends TestCase {

	public function test_tabs_are_grouped_ordered_and_empty_tabs_are_omitted(): void {
		$form = new Form( 'settings', 'save', 'nonce' );

		$form->section( 'display', 'Display' )
			->tab( 'display-behavior', 'Display & behavior', 20 )
			->text( 'button_text' );
		$form->section( 'empty', 'Empty' )->tab( 'empty', 'Empty', 5 );
		$form->section( 'features', 'Features' )
			->tab( 'features', 'Features', 10 )
			->toggle( 'export_enabled' );
		$form->section( 'additional-display', 'Additional display' )
			->tab( 'display-behavior', 'Display & behavior', 20 )
			->select( 'button_location' );
		$form->section( 'legacy', 'Legacy' )->text( 'legacy_label' );

		$tabs = $form->getTabs();

		self::assertSame( [ 'features', 'display-behavior' ], array_column( $tabs, 'id' ) );
		self::assertCount( 2, $tabs[1]['sections'] );
		self::assertSame( 'display-behavior', $form->resolveTabId( 'display-behavior' ) );
		self::assertSame( 'features', $form->resolveTabId( 'unknown' ) );
		self::assertSame(
			[ 'button_text', 'export_enabled', 'button_location', 'legacy_label' ],
			array_keys( $form->getFields() )
		);
	}

}
