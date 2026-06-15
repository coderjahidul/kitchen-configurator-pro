<?php
/**
 * Builds and parses product preset configuration for the visual admin form.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

/**
 * Converts between configuration JSON and visual admin form state.
 */
final class ProductPresetFormSerializer {

	/**
	 * Default empty visual form state.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return array(
			'group_title'     => '',
			'subtitle'          => '',
			'preview_image'     => '',
			'show_storefront'   => false,
			'default_color'     => '',
			'default_height'    => '',
			'summary'           => array(),
			'part_groups'       => array(),
			'parts'             => array(),
			'option_groups'     => array(),
			'colors'            => array(),
			'heights'           => array(),
			'spec_dimensions'   => '',
			'spec_includes'     => '',
			'plinth_label'      => '',
			'plinth_unit_label' => '',
			'plinth_subtotal'   => 0.0,
		);
	}

	/**
	 * Supported option group types for the admin form.
	 *
	 * @return array<string, string>
	 */
	public function option_type_labels(): array {
		return array(
			'color'  => __( 'Color', 'kitchen-configurator-pro' ),
			'height' => __( 'Height', 'kitchen-configurator-pro' ),
			'width'  => __( 'Width', 'kitchen-configurator-pro' ),
			'depth'  => __( 'Depth', 'kitchen-configurator-pro' ),
			'custom' => __( 'Custom', 'kitchen-configurator-pro' ),
		);
	}

	public function part_type_labels(): array {
		return array(
			'cabinet'   => __( 'Cabinet', 'kitchen-configurator-pro' ),
			'panels'    => __( 'Panels', 'kitchen-configurator-pro' ),
			'hardware'  => __( 'Hardware', 'kitchen-configurator-pro' ),
			'finishing' => __( 'Finishing', 'kitchen-configurator-pro' ),
			'custom'    => __( 'Custom', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * Parse stored configuration JSON into visual form values.
	 *
	 * @param string $json Configuration JSON.
	 * @return array<string, mixed>
	 */
	public function from_configuration_json( string $json ): array {
		$state  = $this->defaults();
		$config = json_decode( trim( $json ), true );

		if ( ! is_array( $config ) ) {
			return $state;
		}

		$options = is_array( $config['product_options'] ?? null ) ? $config['product_options'] : array();

		$state['group_title']     = (string) ( $options['group_title'] ?? '' );
		$state['subtitle']        = (string) ( $options['subtitle'] ?? '' );
		$state['preview_image']   = (string) ( $options['preview_image'] ?? '' );
		$state['show_storefront'] = ! empty( $options['show_storefront'] );
		$state['default_color']   = (string) ( $options['default_color'] ?? '' );
		$state['default_height']  = (string) ( $options['default_height'] ?? '' );
		$state['summary']         = $this->normalize_summary_rows( $options['summary'] ?? array() );
		$state['parts']           = $this->normalize_part_rows( $options['parts'] ?? array() );
		$state['part_groups']     = $this->resolve_part_groups(
			is_array( $options['part_groups'] ?? null ) ? $options['part_groups'] : array(),
			$state['parts']
		);
		$state['colors']          = $this->normalize_color_rows( $options['colors'] ?? array() );
		$state['heights']         = $this->normalize_height_rows( $options['heights'] ?? array() );
		$state['option_groups']   = $this->resolve_option_groups(
			is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array(),
			$state['colors'],
			$state['heights'],
			$state['default_color'],
			$state['default_height']
		);

		$specs = is_array( $options['specs'] ?? null ) ? $options['specs'] : array();
		$state['spec_dimensions'] = $this->lines_from_list( $specs['dimensions'] ?? array() );
		$state['spec_includes']   = $this->lines_from_list( $specs['includes'] ?? array() );

		$plinth = is_array( $options['plinth_extra'] ?? null ) ? $options['plinth_extra'] : array();
		$state['plinth_label']      = (string) ( $plinth['label'] ?? '' );
		$state['plinth_unit_label'] = (string) ( $plinth['unit_label'] ?? '' );
		$state['plinth_subtotal']   = (float) ( $plinth['subtotal'] ?? 0 );

		return $state;
	}

	/**
	 * Build configuration JSON from visual form POST data.
	 *
	 * @param array<string, mixed> $post            Raw $_POST['kcp_preset'] array.
	 * @param int                  $layout_id       Layout ID.
	 * @param array<string, mixed> $existing_config Existing decoded configuration.
	 * @return string
	 */
	public function to_configuration_json( array $post, int $layout_id, array $existing_config = array() ): string {
		$option_groups = $this->build_option_groups( $post['option_groups'] ?? array() );
		$part_groups   = $this->build_part_groups( $post['part_groups'] ?? array() );
		$legacy        = $this->legacy_options_from_groups( $option_groups );

		$product_options = array(
			'group_title'     => sanitize_text_field( (string) ( $post['group_title'] ?? '' ) ),
			'subtitle'        => sanitize_text_field( (string) ( $post['subtitle'] ?? '' ) ),
			'preview_image'   => esc_url_raw( (string) ( $post['preview_image'] ?? '' ) ),
			'show_storefront' => ! empty( $post['show_storefront'] ),
			'default_color'   => sanitize_key( (string) ( $legacy['default_color'] ?? '' ) ),
			'default_height'  => sanitize_key( (string) ( $legacy['default_height'] ?? '' ) ),
			'option_groups'   => $option_groups,
			'part_groups'     => $part_groups,
			'summary'         => $this->build_summary_rows( $post['summary'] ?? array() ),
			'parts'           => $this->flatten_part_groups( $part_groups ),
			'colors'          => $legacy['colors'],
			'heights'         => $legacy['heights'],
			'specs'           => array(
				'dimensions' => $this->list_from_lines( (string) ( $post['spec_dimensions'] ?? '' ) ),
				'includes'   => $this->list_from_lines( (string) ( $post['spec_includes'] ?? '' ) ),
			),
		);

		$plinth_label = sanitize_text_field( (string) ( $post['plinth_label'] ?? '' ) );
		$plinth_unit  = sanitize_text_field( (string) ( $post['plinth_unit_label'] ?? '' ) );
		$plinth_total = (float) ( $post['plinth_subtotal'] ?? 0 );

		if ( '' !== $plinth_label || '' !== $plinth_unit || $plinth_total > 0 ) {
			$product_options['plinth_extra'] = array(
				'label'      => $plinth_label,
				'unit_label' => $plinth_unit,
				'subtotal'   => $plinth_total,
			);
		}

		$config = array_merge(
			array(
				'schema_version' => (string) ( $existing_config['schema_version'] ?? '1.0' ),
				'layout_id'      => $layout_id,
				'title'          => sanitize_text_field( (string) ( $existing_config['title'] ?? '' ) ),
				'cabinets'       => is_array( $existing_config['cabinets'] ?? null ) ? $existing_config['cabinets'] : array(),
				'global_options' => is_array( $existing_config['global_options'] ?? null ) ? $existing_config['global_options'] : array(),
			),
			array( 'product_options' => $product_options )
		);

		return wp_json_encode( $config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}';
	}

	/**
	 * @param array<int, mixed> $rows Raw summary rows.
	 * @return array<int, array{label: string, value: string}>
	 */
	private function normalize_summary_rows( array $rows ): array {
		$normalized = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
			$value = sanitize_text_field( (string) ( $row['value'] ?? '' ) );

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$normalized[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $rows Raw part rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_part_rows( array $rows ): array {
		$normalized = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $row['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$normalized[] = array(
				'id'          => $id,
				'label'       => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
				'image_url'   => esc_url_raw( (string) ( $row['image_url'] ?? '' ) ),
				'price'       => (float) ( $row['price'] ?? 0 ),
				'editable'    => ! empty( $row['editable'] ),
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $rows Raw color rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_color_rows( array $rows ): array {
		$normalized = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $row['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$normalized[] = array(
				'id'             => $id,
				'label'          => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'image_url'      => esc_url_raw( (string) ( $row['image_url'] ?? '' ) ),
				'price_modifier' => (float) ( $row['price_modifier'] ?? 0 ),
				'note'           => sanitize_text_field( (string) ( $row['note'] ?? '' ) ),
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $rows Raw height rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_height_rows( array $rows ): array {
		$normalized = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $row['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$normalized[] = array(
				'id'             => $id,
				'label'          => sanitize_text_field( (string) ( $row['label'] ?? '' ) ),
				'price_modifier' => (float) ( $row['price_modifier'] ?? 0 ),
			);
		}

		return $normalized;
	}

	/**
	 * @param mixed $list String list or array.
	 * @return string
	 */
	private function lines_from_list( mixed $list ): string {
		if ( ! is_array( $list ) ) {
			return '';
		}

		$lines = array();

		foreach ( $list as $line ) {
			$line = sanitize_text_field( (string) $line );

			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * @param string $text Multiline text.
	 * @return array<int, string>
	 */
	private function list_from_lines( string $text ): array {
		$lines = preg_split( '/\r\n|\r|\n/', $text ) ?: array();
		$list  = array();

		foreach ( $lines as $line ) {
			$line = sanitize_text_field( (string) $line );

			if ( '' !== $line ) {
				$list[] = $line;
			}
		}

		return $list;
	}

	/**
	 * @param mixed $rows Posted summary rows.
	 * @return array<int, array{label: string, value: string}>
	 */
	private function build_summary_rows( mixed $rows ): array {
		return $this->normalize_summary_rows( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * @param mixed $rows Posted part rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_part_rows( mixed $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$built = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$part = $this->normalize_part_row( $row );

			if ( null === $part ) {
				continue;
			}

			$built[] = $part;
		}

		return $built;
	}

	/**
	 * @param array<string, mixed> $row Raw part row.
	 * @return array<string, mixed>|null
	 */
	private function normalize_part_row( array $row ): ?array {
		$id = sanitize_key( (string) ( $row['id'] ?? '' ) );

		if ( '' === $id ) {
			$id = sanitize_key( sanitize_title( (string) ( $row['label'] ?? 'part' ) ) );
		}

		if ( '' === $id ) {
			return null;
		}

		$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );

		if ( '' === $label ) {
			return null;
		}

		$part = array(
			'id'          => $id,
			'label'       => $label,
			'description' => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
			'image_url'   => esc_url_raw( (string) ( $row['image_url'] ?? '' ) ),
			'price'       => (float) ( $row['price'] ?? 0 ),
			'editable'    => ! empty( $row['editable'] ),
		);

		$group_id = sanitize_key( (string) ( $row['group_id'] ?? '' ) );

		if ( '' !== $group_id ) {
			$part['group_id'] = $group_id;
		}

		$group_label = sanitize_text_field( (string) ( $row['group_label'] ?? '' ) );

		if ( '' !== $group_label ) {
			$part['group_label'] = $group_label;
		}

		return $part;
	}

	/**
	 * @param array<int, mixed> $groups Stored part groups.
	 * @param array<int, mixed> $parts  Legacy flat parts.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_part_groups( array $groups, array $parts ): array {
		$normalized = $this->normalize_part_groups( $groups );

		if ( ! empty( $normalized ) ) {
			return $this->repair_nested_part_groups( $normalized );
		}

		if ( empty( $parts ) ) {
			return array();
		}

		return $this->legacy_part_groups_from_parts( $parts );
	}

	/**
	 * Convert wrongly nested flat parts (all items in one main group) into one group per part.
	 *
	 * @param array<int, array<string, mixed>> $groups Normalized part groups.
	 * @return array<int, array<string, mixed>>
	 */
	private function repair_nested_part_groups( array $groups ): array {
		if ( 1 !== count( $groups ) ) {
			return $groups;
		}

		$group = $groups[0];
		$items = is_array( $group['items'] ?? null ) ? $group['items'] : array();

		if ( count( $items ) <= 1 ) {
			return $groups;
		}

		$uses_legacy_item_shape = false;

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['label'] ) && ! isset( $item['value'] ) ) {
				$uses_legacy_item_shape = true;
				break;
			}
		}

		if ( ! $uses_legacy_item_shape && 'main' !== sanitize_key( (string) ( $group['id'] ?? '' ) ) ) {
			return $groups;
		}

		$legacy_parts = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$legacy_parts[] = array(
				'id'          => (string) ( $item['id'] ?? '' ),
				'label'       => (string) ( $item['label'] ?? $item['value'] ?? '' ),
				'description' => (string) ( $item['description'] ?? '' ),
				'image_url'   => (string) ( $item['image_url'] ?? '' ),
				'price'       => (float) ( $item['price'] ?? 0 ),
				'editable'    => ! empty( $item['editable'] ),
			);
		}

		return $this->legacy_part_groups_from_parts( $legacy_parts );
	}

	/**
	 * @param array<int, mixed> $groups Raw part groups.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_part_groups( array $groups ): array {
		$normalized = array();
		$labels     = $this->part_type_labels();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $group['type'] ?? 'custom' ) );

			if ( ! isset( $labels[ $type ] ) ) {
				$type = 'custom';
			}

			$id = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( '' === $id ) {
				$id = 'custom' === $type
					? sanitize_key( sanitize_title( (string) ( $group['label'] ?? 'parts' ) ) )
					: $type;
			}

			if ( '' === $id ) {
				continue;
			}

			$items = $this->normalize_part_group_items( is_array( $group['items'] ?? null ) ? $group['items'] : array() );

			$label = sanitize_text_field( (string) ( $group['label'] ?? '' ) );

			if ( '' === $label ) {
				$label = (string) ( $labels[ $type ] ?? ucfirst( $id ) );
			}

			$normalized[] = array(
				'id'           => $id,
				'type'         => $type,
				'label'        => $label,
				'description'  => sanitize_text_field( (string) ( $group['description'] ?? '' ) ),
				'image_url'    => esc_url_raw( (string) ( $group['image_url'] ?? '' ) ),
				'price'        => (float) ( $group['price'] ?? 0 ),
				'editable'     => ! empty( $group['editable'] ),
				'default_item' => sanitize_key( (string) ( $group['default_item'] ?? '' ) ),
				'items'        => $items,
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $items Raw part items.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_part_group_items( array $items ): array {
		$normalized = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$value = sanitize_text_field( (string) ( $item['value'] ?? $item['label'] ?? '' ) );

			if ( '' === $value ) {
				continue;
			}

			$normalized[] = array(
				'id'          => $id,
				'value'       => $value,
				'description' => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
				'image_url'   => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'price'       => (float) ( $item['price'] ?? 0 ),
			);
		}

		return $normalized;
	}

	/**
	 * @param mixed $groups Posted part groups.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_part_groups( mixed $groups ): array {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$built = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $group['type'] ?? 'custom' ) );
			$id   = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( '' === $id ) {
				$id = 'custom' === $type
					? sanitize_key( sanitize_title( (string) ( $group['label'] ?? 'parts' ) ) )
					: $type;
			}

			if ( '' === $id ) {
				continue;
			}

			$items = $this->build_part_group_items( $group['items'] ?? array() );

			$label = sanitize_text_field( (string) ( $group['label'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			$built[] = array(
				'id'           => $id,
				'type'         => $type,
				'label'        => $label,
				'description'  => sanitize_text_field( (string) ( $group['description'] ?? '' ) ),
				'image_url'    => esc_url_raw( (string) ( $group['image_url'] ?? '' ) ),
				'price'        => (float) ( $group['price'] ?? 0 ),
				'editable'     => ! empty( $group['editable'] ),
				'default_item' => sanitize_key( (string) ( $group['default_item'] ?? '' ) ),
				'items'        => $items,
			);
		}

		return $built;
	}

	/**
	 * @param mixed $items Posted part items.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_part_group_items( mixed $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		return $this->normalize_part_group_items( $items );
	}

	/**
	 * @param array<int, array<string, mixed>> $groups Part groups (one group = one cart part).
	 * @return array<int, array<string, mixed>>
	 */
	private function flatten_part_groups( array $groups ): array {
		$parts = array();

		foreach ( $groups as $group ) {
			$part = $this->part_from_group( $group );

			if ( null !== $part ) {
				$parts[] = $part;
			}
		}

		return $parts;
	}

	/**
	 * @param array<string, mixed> $group Part group.
	 * @return array<string, mixed>|null
	 */
	private function part_from_group( array $group ): ?array {
		$id = sanitize_key( (string) ( $group['id'] ?? '' ) );

		if ( '' === $id ) {
			$id = sanitize_key( sanitize_title( (string) ( $group['label'] ?? 'part' ) ) );
		}

		$label = sanitize_text_field( (string) ( $group['label'] ?? '' ) );

		if ( '' === $id || '' === $label ) {
			return null;
		}

		$items         = is_array( $group['items'] ?? null ) ? $group['items'] : array();
		$height_prices = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$item_id = sanitize_key( (string) ( $item['id'] ?? '' ) );

			if ( '' === $item_id ) {
				continue;
			}

			$height_prices[ $item_id ] = (float) ( $item['price'] ?? 0 );
		}

		$price        = (float) ( $group['price'] ?? 0 );
		$default_item = sanitize_key( (string) ( $group['default_item'] ?? '' ) );

		if ( '' !== $default_item && isset( $height_prices[ $default_item ] ) ) {
			$price = $height_prices[ $default_item ];
		} elseif ( ! empty( $height_prices ) ) {
			$price = (float) reset( $height_prices );
		}

		$part = array(
			'id'          => $id,
			'label'       => $label,
			'description' => sanitize_text_field( (string) ( $group['description'] ?? '' ) ),
			'image_url'   => esc_url_raw( (string) ( $group['image_url'] ?? '' ) ),
			'price'       => $price,
			'editable'    => ! empty( $group['editable'] ),
		);

		if ( ! empty( $height_prices ) ) {
			$part['height_prices'] = $height_prices;
		}

		return $part;
	}

	/**
	 * @param array<int, mixed> $parts Legacy flat parts.
	 * @return array<int, array<string, mixed>>
	 */
	private function legacy_part_groups_from_parts( array $parts ): array {
		$groups = array();

		foreach ( $parts as $part ) {
			if ( ! is_array( $part ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $part['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $part['label'] ?? '' ) );

			if ( '' === $label ) {
				continue;
			}

			$items         = array();
			$height_prices = is_array( $part['height_prices'] ?? null ) ? $part['height_prices'] : array();
			$default_item  = '';

			foreach ( $height_prices as $height_id => $height_price ) {
				$height_key = sanitize_key( (string) $height_id );

				if ( '' === $height_key ) {
					continue;
				}

				if ( '' === $default_item ) {
					$default_item = $height_key;
				}

				$items[] = array(
					'id'          => $height_key,
					'value'       => $height_key,
					'description' => '',
					'image_url'   => '',
					'price'       => (float) $height_price,
				);
			}

			$groups[] = array(
				'id'           => $id,
				'type'         => 'custom',
				'label'        => $label,
				'description'  => sanitize_text_field( (string) ( $part['description'] ?? '' ) ),
				'image_url'    => esc_url_raw( (string) ( $part['image_url'] ?? '' ) ),
				'price'        => (float) ( $part['price'] ?? 0 ),
				'editable'     => ! empty( $part['editable'] ),
				'default_item' => $default_item,
				'items'        => $items,
			);
		}

		return $groups;
	}

	/**
	 * @param array<int, mixed> $groups        Stored option groups.
	 * @param array<int, mixed> $colors        Legacy color rows.
	 * @param array<int, mixed> $heights       Legacy height rows.
	 * @param string            $default_color Legacy default color ID.
	 * @param string            $default_height Legacy default height ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_option_groups( array $groups, array $colors, array $heights, string $default_color, string $default_height ): array {
		$normalized = $this->normalize_option_groups( $groups );

		if ( ! empty( $normalized ) ) {
			return $normalized;
		}

		$migrated = array();

		if ( ! empty( $colors ) ) {
			$migrated[] = $this->legacy_group_from_colors( $colors, $default_color );
		}

		if ( ! empty( $heights ) ) {
			$migrated[] = $this->legacy_group_from_heights( $heights, $default_height );
		}

		return $migrated;
	}

	/**
	 * @param array<int, mixed> $groups Raw option groups.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_option_groups( array $groups ): array {
		$normalized = array();
		$labels     = $this->option_type_labels();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $group['type'] ?? 'custom' ) );

			if ( ! isset( $labels[ $type ] ) ) {
				$type = 'custom';
			}

			$id = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( '' === $id ) {
				$id = 'custom' === $type
					? sanitize_key( sanitize_title( (string) ( $group['label'] ?? 'option' ) ) )
					: $type;
			}

			if ( '' === $id ) {
				continue;
			}

			$items = $this->normalize_option_items( is_array( $group['items'] ?? null ) ? $group['items'] : array() );

			if ( empty( $items ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $group['label'] ?? '' ) );

			if ( '' === $label ) {
				$label = (string) ( $labels[ $type ] ?? ucfirst( $id ) );
			}

			$normalized[] = array(
				'id'           => $id,
				'type'         => $type,
				'label'        => $label,
				'default_item' => sanitize_key( (string) ( $group['default_item'] ?? '' ) ),
				'items'        => $items,
			);
		}

		return $normalized;
	}

	/**
	 * @param array<int, mixed> $items Raw option items.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_option_items( array $items ): array {
		$normalized = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );

			if ( '' === $id ) {
				continue;
			}

			$value = sanitize_text_field( (string) ( $item['value'] ?? $item['label'] ?? '' ) );

			if ( '' === $value ) {
				continue;
			}

			$normalized[] = array(
				'id'          => $id,
				'value'       => $value,
				'price'       => (float) ( $item['price'] ?? $item['price_modifier'] ?? 0 ),
				'image_url'   => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $item['description'] ?? $item['note'] ?? '' ) ),
			);
		}

		return $normalized;
	}

	/**
	 * @param mixed $groups Posted option groups.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_option_groups( mixed $groups ): array {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$built = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$type = sanitize_key( (string) ( $group['type'] ?? 'custom' ) );
			$id   = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( '' === $id ) {
				$id = 'custom' === $type
					? sanitize_key( sanitize_title( (string) ( $group['label'] ?? 'option' ) ) )
					: $type;
			}

			if ( '' === $id ) {
				continue;
			}

			$items = $this->build_option_items( $group['items'] ?? array() );

			if ( empty( $items ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $group['label'] ?? '' ) );

			if ( '' === $label ) {
				$label = (string) ( $this->option_type_labels()[ $type ] ?? ucfirst( $id ) );
			}

			$built[] = array(
				'id'           => $id,
				'type'         => $type,
				'label'        => $label,
				'default_item' => sanitize_key( (string) ( $group['default_item'] ?? '' ) ),
				'items'        => $items,
			);
		}

		return $built;
	}

	/**
	 * @param mixed $items Posted option items.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_option_items( mixed $items ): array {
		if ( ! is_array( $items ) ) {
			return array();
		}

		$built = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$id = sanitize_key( (string) ( $item['id'] ?? '' ) );

			if ( '' === $id ) {
				$id = sanitize_key( sanitize_title( (string) ( $item['value'] ?? 'option' ) ) );
			}

			if ( '' === $id ) {
				continue;
			}

			$value = sanitize_text_field( (string) ( $item['value'] ?? '' ) );

			if ( '' === $value ) {
				continue;
			}

			$built[] = array(
				'id'          => $id,
				'value'       => $value,
				'price'       => (float) ( $item['price'] ?? 0 ),
				'image_url'   => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
			);
		}

		return $built;
	}

	/**
	 * Keep legacy colors/heights/default keys in sync with option groups.
	 *
	 * @param array<int, array<string, mixed>> $groups Normalized option groups.
	 * @return array{colors: array<int, array<string, mixed>>, heights: array<int, array<string, mixed>>, default_color: string, default_height: string}
	 */
	private function legacy_options_from_groups( array $groups ): array {
		$legacy = array(
			'colors'         => array(),
			'heights'        => array(),
			'default_color'  => '',
			'default_height' => '',
		);

		foreach ( $groups as $group ) {
			$type = sanitize_key( (string) ( $group['type'] ?? '' ) );
			$id   = sanitize_key( (string) ( $group['id'] ?? '' ) );

			if ( $this->is_color_group( $type, $id ) ) {
				$legacy['colors']        = $this->group_to_color_rows( $group );
				$legacy['default_color'] = sanitize_key( (string) ( $group['default_item'] ?? '' ) );
				continue;
			}

			if ( $this->is_height_group( $type, $id ) ) {
				$legacy['heights']        = $this->group_to_height_rows( $group );
				$legacy['default_height'] = sanitize_key( (string) ( $group['default_item'] ?? '' ) );
			}
		}

		return $legacy;
	}

	/**
	 * @param string $type Group type.
	 * @param string $id   Group ID.
	 * @return bool
	 */
	private function is_color_group( string $type, string $id ): bool {
		return 'color' === $type || in_array( $id, array( 'color', 'colors' ), true );
	}

	/**
	 * @param string $type Group type.
	 * @param string $id   Group ID.
	 * @return bool
	 */
	private function is_height_group( string $type, string $id ): bool {
		return 'height' === $type || in_array( $id, array( 'height', 'heights' ), true );
	}

	/**
	 * @param array<string, mixed> $group Option group.
	 * @return array<int, array<string, mixed>>
	 */
	private function group_to_color_rows( array $group ): array {
		$rows = array();

		foreach ( is_array( $group['items'] ?? null ) ? $group['items'] : array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$rows[] = array(
				'id'             => sanitize_key( (string) ( $item['id'] ?? '' ) ),
				'label'          => sanitize_text_field( (string) ( $item['value'] ?? '' ) ),
				'image_url'      => esc_url_raw( (string) ( $item['image_url'] ?? '' ) ),
				'price_modifier' => (float) ( $item['price'] ?? 0 ),
				'note'           => sanitize_text_field( (string) ( $item['description'] ?? '' ) ),
			);
		}

		return $rows;
	}

	/**
	 * @param array<string, mixed> $group Option group.
	 * @return array<int, array<string, mixed>>
	 */
	private function group_to_height_rows( array $group ): array {
		$rows = array();

		foreach ( is_array( $group['items'] ?? null ) ? $group['items'] : array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$rows[] = array(
				'id'             => sanitize_key( (string) ( $item['id'] ?? '' ) ),
				'label'          => sanitize_text_field( (string) ( $item['value'] ?? '' ) ),
				'price_modifier' => (float) ( $item['price'] ?? 0 ),
			);
		}

		return $rows;
	}

	/**
	 * @param array<int, mixed> $colors Legacy color rows.
	 * @param string            $default_color Default color ID.
	 * @return array<string, mixed>
	 */
	private function legacy_group_from_colors( array $colors, string $default_color ): array {
		$items = array();

		foreach ( $colors as $color ) {
			if ( ! is_array( $color ) ) {
				continue;
			}

			$items[] = array(
				'id'          => sanitize_key( (string) ( $color['id'] ?? '' ) ),
				'value'       => sanitize_text_field( (string) ( $color['label'] ?? '' ) ),
				'price'       => (float) ( $color['price_modifier'] ?? 0 ),
				'image_url'   => esc_url_raw( (string) ( $color['image_url'] ?? '' ) ),
				'description' => sanitize_text_field( (string) ( $color['note'] ?? '' ) ),
			);
		}

		return array(
			'id'           => 'color',
			'type'         => 'color',
			'label'        => __( 'Color', 'kitchen-configurator-pro' ),
			'default_item' => sanitize_key( $default_color ),
			'items'        => $items,
		);
	}

	/**
	 * @param array<int, mixed> $heights Legacy height rows.
	 * @param string            $default_height Default height ID.
	 * @return array<string, mixed>
	 */
	private function legacy_group_from_heights( array $heights, string $default_height ): array {
		$items = array();

		foreach ( $heights as $height ) {
			if ( ! is_array( $height ) ) {
				continue;
			}

			$items[] = array(
				'id'          => sanitize_key( (string) ( $height['id'] ?? '' ) ),
				'value'       => sanitize_text_field( (string) ( $height['label'] ?? '' ) ),
				'price'       => (float) ( $height['price_modifier'] ?? 0 ),
				'image_url'   => '',
				'description' => '',
			);
		}

		return array(
			'id'           => 'height',
			'type'         => 'height',
			'label'        => __( 'Height', 'kitchen-configurator-pro' ),
			'default_item' => sanitize_key( $default_height ),
			'items'        => $items,
		);
	}
}
