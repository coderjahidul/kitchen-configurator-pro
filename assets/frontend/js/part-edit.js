/**
 * Cart part edit page — single item dropdown.
 */

function formatDutchPartPrice( amount ) {
	return `${ Math.round( amount ).toLocaleString( 'nl-NL' ) },-`;
}

function readItems() {
	const node = document.getElementById( 'kcp-part-edit-data' );

	if ( ! node ) {
		return [];
	}

	try {
		const parsed = JSON.parse( node.textContent || '[]' );
		return Array.isArray( parsed ) ? parsed : [];
	} catch ( error ) {
		return [];
	}
}

function findItemById( items, itemId ) {
	return items.find( ( item ) => String( item.id || '' ) === String( itemId || '' ) ) || null;
}

function updateView( price, imageUrl ) {
	const priceNode = document.getElementById( 'kcp-part-edit-price' );
	const imageNode = document.getElementById( 'kcp-part-edit-image-main' );

	if ( priceNode ) {
		priceNode.textContent = formatDutchPartPrice( Number( price || 0 ) );
	}

	if ( imageNode && imageUrl ) {
		imageNode.src = imageUrl;
	}
}

function initPartEditPage( root ) {
	const items = readItems();
	const select = root.querySelector( '[data-kcp-part-item-select]' );

	if ( ! select ) {
		return;
	}

	const syncSelection = () => {
		const option = select.options[ select.selectedIndex ];
		const item = findItemById( items, select.value );
		const price = item ? Number( item.price || 0 ) : Number( option?.dataset?.price || 0 );
		const imageUrl = item?.image_url || option?.dataset?.imageUrl || '';

		updateView( price, imageUrl );
	};

	select.addEventListener( 'change', syncSelection );
	syncSelection();
}

function bootPartEditPage() {
	const root = document.querySelector( '[data-kcp-part-edit]' );

	if ( root ) {
		initPartEditPage( root );
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', bootPartEditPage );
} else {
	bootPartEditPage();
}
