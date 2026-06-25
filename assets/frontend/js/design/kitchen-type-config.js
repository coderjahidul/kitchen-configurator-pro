/**
 * Kitchen type identifiers (greep vs greeploos).
 */

export const KITCHEN_TYPE_GREP = 'greep';
export const KITCHEN_TYPE_GREEPLOOS = 'greeploos';
export const KITCHEN_TYPE_PARAM = 'kcp_kitchen_type';
export const DEFAULT_KITCHEN_TYPE = KITCHEN_TYPE_GREP;

export const KITCHEN_TYPE_LABELS = {
	[ KITCHEN_TYPE_GREP ]: 'keuken met greep',
	[ KITCHEN_TYPE_GREEPLOOS ]: 'keuken greeploos',
};

/**
 * @param {string|null|undefined} value Raw kitchen type value.
 * @return {string}
 */
export function normalizeKitchenType( value ) {
	const normalized = String( value || '' ).trim().toLowerCase();

	if ( KITCHEN_TYPE_GREEPLOOS === normalized ) {
		return KITCHEN_TYPE_GREEPLOOS;
	}

	return KITCHEN_TYPE_GREP;
}

/**
 * @param {string} kitchenType Normalized kitchen type.
 * @return {string}
 */
export function kitchenTypeLabel( kitchenType ) {
	return KITCHEN_TYPE_LABELS[ normalizeKitchenType( kitchenType ) ] || KITCHEN_TYPE_LABELS[ DEFAULT_KITCHEN_TYPE ];
}

/**
 * @param {string} kitchenType Normalized kitchen type.
 * @return {boolean}
 */
export function showsHandleStrip( kitchenType ) {
	return KITCHEN_TYPE_GREP === normalizeKitchenType( kitchenType );
}
