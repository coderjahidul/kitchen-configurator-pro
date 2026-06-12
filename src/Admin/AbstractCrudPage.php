<?php
/**
 * Base admin CRUD page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Contracts\RepositoryInterface;
use KitchenConfiguratorPro\Security\CapabilityManager;
use KitchenConfiguratorPro\Support\Arr;
use KitchenConfiguratorPro\Support\Helpers;

/**
 * Reusable list/add/edit/delete admin page.
 */
abstract class AbstractCrudPage {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	protected Container $container;

	/**
	 * Admin notice messages.
	 *
	 * @var array<int, array{type: string, message: string}>
	 */
	protected array $notices = array();

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * WordPress admin menu slug.
	 *
	 * @return string
	 */
	abstract public function slug(): string;

	/**
	 * Singular entity label.
	 *
	 * @return string
	 */
	abstract public function entity_label(): string;

	/**
	 * Plural entity label.
	 *
	 * @return string
	 */
	abstract public function entity_label_plural(): string;

	/**
	 * Repository class name.
	 *
	 * @return class-string<RepositoryInterface>
	 */
	abstract protected function repository_class(): string;

	/**
	 * List table columns.
	 *
	 * @return array<string, string>
	 */
	abstract protected function list_columns(): array;

	/**
	 * Form field definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	abstract protected function form_fields(): array;

	/**
	 * Whether catalog cache should be invalidated on save.
	 *
	 * @return bool
	 */
	protected function invalidates_catalog_cache(): bool {
		return true;
	}

	/**
	 * Get repository instance.
	 *
	 * @return RepositoryInterface
	 */
	protected function repository(): RepositoryInterface {
		return $this->container->get( $this->repository_class() );
	}

	/**
	 * Handle request and render page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kitchen-configurator-pro' ) );
		}

		$this->handle_actions();

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : 'list';

		match ( $action ) {
			'add', 'edit' => $this->render_form( 'edit' === $action ),
			default       => $this->render_list(),
		};
	}

	/**
	 * Process POST/GET actions.
	 *
	 * @return void
	 */
	protected function handle_actions(): void {
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['kcp_action'] ) ) {
			$this->handle_post();
			return;
		}

		if ( isset( $_GET['action'], $_GET['_wpnonce'] ) && 'delete' === sanitize_key( wp_unslash( (string) $_GET['action'] ) ) ) {
			$this->handle_delete();
		}
	}

	/**
	 * Handle form submission.
	 *
	 * @return void
	 */
	protected function handle_post(): void {
		$nonce_action = $this->nonce_action();
		$nonce        = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			$this->add_notice( 'error', __( 'Security check failed.', 'kitchen-configurator-pro' ) );
			return;
		}

		$action = sanitize_key( wp_unslash( (string) $_POST['kcp_action'] ) );
		$data   = $this->collect_post_data();

		$validation_error = $this->validate( $data );

		if ( null !== $validation_error ) {
			$this->add_notice( 'error', $validation_error );
			return;
		}

		$repository = $this->repository();

		if ( 'create' === $action ) {
			$result = $repository->create( $data );

			if ( null === $result ) {
				$this->add_notice( 'error', __( 'Failed to create record.', 'kitchen-configurator-pro' ) );
				return;
			}

			if ( $this->invalidates_catalog_cache() ) {
				Helpers::bump_catalog_cache_version();
			}

			$this->redirect_with_notice(
				'created',
				add_query_arg(
					array(
						'page'   => $this->slug(),
						'action' => 'edit',
						'id'     => Arr::to_array( $result )['id'] ?? 0,
					),
					admin_url( 'admin.php' )
				)
			);
		}

		if ( 'update' === $action ) {
			$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

			if ( $id <= 0 ) {
				$this->add_notice( 'error', __( 'Invalid record ID.', 'kitchen-configurator-pro' ) );
				return;
			}

			$result = $repository->update( $id, $data );

			if ( null === $result ) {
				$this->add_notice( 'error', __( 'Failed to update record.', 'kitchen-configurator-pro' ) );
				return;
			}

			if ( $this->invalidates_catalog_cache() ) {
				Helpers::bump_catalog_cache_version();
			}

			$this->redirect_with_notice(
				'updated',
				add_query_arg(
					array(
						'page'   => $this->slug(),
						'action' => 'edit',
						'id'     => $id,
					),
					admin_url( 'admin.php' )
				)
			);
		}
	}

	/**
	 * Handle delete action.
	 *
	 * @return void
	 */
	protected function handle_delete(): void {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		if ( $id <= 0 ) {
			$this->add_notice( 'error', __( 'Invalid record ID.', 'kitchen-configurator-pro' ) );
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ), $this->delete_nonce_action( $id ) ) ) {
			$this->add_notice( 'error', __( 'Security check failed.', 'kitchen-configurator-pro' ) );
			return;
		}

		if ( ! $this->repository()->delete( $id ) ) {
			$this->add_notice( 'error', __( 'Failed to delete record.', 'kitchen-configurator-pro' ) );
			return;
		}

		if ( $this->invalidates_catalog_cache() ) {
			Helpers::bump_catalog_cache_version();
		}

		$this->redirect_with_notice(
			'deleted',
			add_query_arg( array( 'page' => $this->slug() ), admin_url( 'admin.php' ) )
		);
	}

	/**
	 * Collect POST data from form fields.
	 *
	 * @return array<string, mixed>
	 */
	protected function collect_post_data(): array {
		$data = array();

		foreach ( $this->form_fields() as $key => $field ) {
			$type = (string) ( $field['type'] ?? 'text' );

			if ( 'checkbox' === $type ) {
				$data[ $key ] = isset( $_POST[ $key ] ) ? '1' : '0';
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = wp_unslash( $_POST[ $key ] );

			$data[ $key ] = is_array( $value )
				? array_map( 'sanitize_text_field', $value )
				: $value;
		}

		return $data;
	}

	/**
	 * Validate submitted data.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return string|null Error message or null.
	 */
	protected function validate( array $data ): ?string {
		foreach ( $this->form_fields() as $key => $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			$value = $data[ $key ] ?? '';

			if ( is_string( $value ) && '' === trim( $value ) ) {
				return sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'kitchen-configurator-pro' ),
					(string) ( $field['label'] ?? $key )
				);
			}
		}

		return null;
	}

	/**
	 * Render list view.
	 *
	 * @return void
	 */
	protected function render_list(): void {
		$items = $this->repository()->find_all();

		$this->load_template(
			'crud-list',
			array(
				'page'         => $this,
				'items'        => $items,
				'columns'      => $this->list_columns(),
				'notices'      => $this->resolve_notices(),
				'add_url'      => $this->form_url(),
				'entity_label' => $this->entity_label(),
			)
		);
	}

	/**
	 * Render add/edit form.
	 *
	 * @param bool $is_edit Whether editing existing record.
	 * @return void
	 */
	protected function render_form( bool $is_edit ): void {
		$item  = null;
		$id    = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$values = array();

		if ( $is_edit ) {
			if ( $id <= 0 ) {
				wp_die( esc_html__( 'Invalid record ID.', 'kitchen-configurator-pro' ) );
			}

			$item = $this->repository()->find( $id );

			if ( null === $item ) {
				wp_die( esc_html__( 'Record not found.', 'kitchen-configurator-pro' ) );
			}

			$values = Arr::to_array( $item );
		}

		foreach ( $this->form_fields() as $key => $field ) {
			if ( ! isset( $values[ $key ] ) && isset( $field['default'] ) ) {
				$values[ $key ] = $field['default'];
			}
		}

		$this->load_template(
			'crud-form',
			array_merge(
				array(
					'page'         => $this,
					'is_edit'      => $is_edit,
					'id'           => $id,
					'values'       => $values,
					'fields'       => $this->form_fields(),
					'notices'      => $this->resolve_notices(),
					'list_url'     => $this->list_url(),
					'entity_label' => $this->entity_label(),
					'nonce_action' => $this->nonce_action(),
				),
				$this->form_context( $values )
			)
		);
	}

	/**
	 * Extra template context for forms.
	 *
	 * @param array<string, mixed> $values Current values.
	 * @return array<string, mixed>
	 */
	protected function form_context( array $values ): array {
		return array();
	}

	/**
	 * Format column value for list display.
	 *
	 * @param string               $column Column key.
	 * @param array<string, mixed> $row    Entity data.
	 * @return string
	 */
	public function format_column( string $column, array $row ): string {
		$value = $row[ $column ] ?? '';

		if ( 'is_active' === $column ) {
			return ! empty( $value )
				? '<span class="kcp-status kcp-status--active">' . esc_html__( 'Yes', 'kitchen-configurator-pro' ) . '</span>'
				: '<span class="kcp-status kcp-status--inactive">' . esc_html__( 'No', 'kitchen-configurator-pro' ) . '</span>';
		}

		if ( 'hex_code' === $column && is_string( $value ) && '' !== $value ) {
			return sprintf(
				'<span class="kcp-color-swatch" style="background:%1$s"></span> %2$s',
				esc_attr( $value ),
				esc_html( $value )
			);
		}

		if ( is_bool( $value ) ) {
			return $value ? esc_html__( 'Yes', 'kitchen-configurator-pro' ) : esc_html__( 'No', 'kitchen-configurator-pro' );
		}

		if ( is_scalar( $value ) ) {
			$string = (string) $value;

			if ( strlen( $string ) > 80 ) {
				return esc_html( substr( $string, 0, 77 ) . '...' );
			}

			return esc_html( $string );
		}

		return '';
	}

	/**
	 * List page URL.
	 *
	 * @return string
	 */
	public function list_url(): string {
		return add_query_arg( array( 'page' => $this->slug() ), admin_url( 'admin.php' ) );
	}

	/**
	 * Add form URL.
	 *
	 * @return string
	 */
	public function form_url( int $id = 0 ): string {
		$args = array(
			'page'   => $this->slug(),
			'action' => $id > 0 ? 'edit' : 'add',
		);

		if ( $id > 0 ) {
			$args['id'] = $id;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Delete URL with nonce.
	 *
	 * @param int $id Record ID.
	 * @return string
	 */
	public function delete_url( int $id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'   => $this->slug(),
					'action' => 'delete',
					'id'     => $id,
				),
				admin_url( 'admin.php' )
			),
			$this->delete_nonce_action( $id )
		);
	}

	/**
	 * Nonce action for save forms.
	 *
	 * @return string
	 */
	protected function nonce_action(): string {
		return 'kcp_save_' . $this->slug();
	}

	/**
	 * Nonce action for delete.
	 *
	 * @param int $id Record ID.
	 * @return string
	 */
	protected function delete_nonce_action( int $id ): string {
		return 'kcp_delete_' . $this->slug() . '_' . $id;
	}

	/**
	 * Add admin notice.
	 *
	 * @param string $type    Notice type.
	 * @param string $message Notice message.
	 * @return void
	 */
	protected function add_notice( string $type, string $message ): void {
		$this->notices[] = array(
			'type'    => $type,
			'message' => $message,
		);
	}

	/**
	 * Redirect with flash notice.
	 *
	 * @param string $code Notice code.
	 * @param string $url  Redirect URL.
	 * @return void
	 */
	protected function redirect_with_notice( string $code, string $url ): void {
		set_transient( 'kcp_admin_notice_' . get_current_user_id(), $code, 30 );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Resolve flash and inline notices.
	 *
	 * @return array<int, array{type: string, message: string}>
	 */
	protected function resolve_notices(): array {
		$notices = $this->notices;
		$flash   = get_transient( 'kcp_admin_notice_' . get_current_user_id() );

		if ( is_string( $flash ) ) {
			delete_transient( 'kcp_admin_notice_' . get_current_user_id() );

			$messages = array(
				'created' => __( 'Record created successfully.', 'kitchen-configurator-pro' ),
				'updated' => __( 'Record updated successfully.', 'kitchen-configurator-pro' ),
				'deleted' => __( 'Record deleted successfully.', 'kitchen-configurator-pro' ),
				'saved'   => __( 'Settings saved successfully.', 'kitchen-configurator-pro' ),
			);

			if ( isset( $messages[ $flash ] ) ) {
				$notices[] = array(
					'type'    => 'success',
					'message' => $messages[ $flash ],
				);
			}
		}

		return $notices;
	}

	/**
	 * Load admin template.
	 *
	 * @param string               $template Template name without extension.
	 * @param array<string, mixed> $vars     Template variables.
	 * @return void
	 */
	protected function load_template( string $template, array $vars = array() ): void {
		$path = KCP_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Admin template not found.', 'kitchen-configurator-pro' ) );
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled template vars.
		extract( $vars, EXTR_SKIP );
		include $path;
	}
}
