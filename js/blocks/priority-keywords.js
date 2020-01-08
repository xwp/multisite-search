// ES5 Syntax to avoid build process.
(function(wp) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editPost.PluginDocumentSettingPanel;
	var el = wp.element.createElement;
	var Text = wp.components.TextareaControl;
	var withSelect = wp.data.withSelect;
	var withDispatch = wp.data.withDispatch;
	var __ = wp.i18n.__;

	var mapSelectToProps = function(select) {
		return {
			metaFieldValue: select("core/editor").getEditedPostAttribute(
				"meta"
			)["mss_priority_keywords"]
		};
	};

	var mapDispatchToProps = function(dispatch) {
		return {
			setMetaFieldValue: function(value) {
				dispatch("core/editor").editPost({
					meta: { mss_priority_keywords: value }
				});
			}
		};
	};

	var PriorityKeywordsField = function(props) {
		return el(Text, {
			label: __("Priority Keywords", "multisite-search"),
			value: props.metaFieldValue,
			onChange: function(content) {
				props.setMetaFieldValue(content);
			}
		});
	};

	var PriorityKeywordsFieldWithData = withSelect(mapSelectToProps)(
		PriorityKeywordsField
	);
	var PriorityKeywordsFieldWithDataAndActions = withDispatch(
		mapDispatchToProps
	)(PriorityKeywordsFieldWithData);

	registerPlugin("multisite-search-sidebar", {
		render: function() {
			return el(
				PluginSidebar,
				{
					name: "multisite-search-sidebar",
					icon: "search",
					title: __("Multisite Search", "multisite-search")
				},
				el(
					"div",
					{ className: "plugin-sidebar-content" },
					el(PriorityKeywordsFieldWithDataAndActions)
				)
			);
		}
	});
})(window.wp);
