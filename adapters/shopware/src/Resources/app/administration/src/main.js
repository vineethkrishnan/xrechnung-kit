/**
 * xrechnung-kit-shopware admin entry point.
 *
 * The plugin's contribution to the Shopware administration: a module
 * that adds an XRechnung tab to the order detail page, an API service
 * that wraps the plugin's admin download endpoint, and ACL privileges
 * for the new entity.
 *
 * Same source targets Shopware 6.5 (Vue 2) and 6.6 (Vue 3 in compat
 * mode). Avoid Vue-version-specific APIs (this.$set, Vue.observable,
 * etc.) and stick to Shopware.Component / Component.override patterns.
 */
import './core/service/api/xrechnung-kit.api.service';
import './module/sw-xrechnung-kit';
