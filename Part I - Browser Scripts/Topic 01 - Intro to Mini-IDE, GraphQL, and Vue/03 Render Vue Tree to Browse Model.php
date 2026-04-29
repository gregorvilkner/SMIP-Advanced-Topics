<?php

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.core.js',           array('version' => 'auto', 'relative' => false));
HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.components.min.js', array('version' => 'auto', 'relative' => false));

require_once 'thinkiq_context.php';
$context = new Context();

use Joomla\CMS\Factory;
$user = Factory::getUser();

?>

<!--
    The recursive tree-item component below is a stripped-down adaptation of the
    official Vue.js Tree View example: https://vuejs.org/examples/#tree-view
    If you want to bolt on additional behavior (add/remove nodes, drag-and-drop,
    inline editing, etc.), that example is the recommended starting point.
-->
<script type="text/x-template" id="item-template">
    <li>
        <div class="tree-row"
             :class="{
                'has-children': hasChildren,
                'active': item.id && item.id == activeattrid
             }"
             @click="toggle">
            <i v-if="hasChildren"
               class="fa fa-chevron-right tree-caret"
               :class="{'open': isOpen}"></i>
            <span v-else class="tree-leaf-spacer"></span>
            <span class="tree-label" @click.stop="$emit('on-attribute-click', item.id)">{{item.title}}</span>
        </div>
        <ul v-show="isOpen" v-if="hasChildren">
            <tree-item
                v-for="child in item.children"
                :key="child.id || child.name"
                :item="child"
                @on-attribute-click="onAttributeClick"
                :activeattrid="activeattrid"
            ></tree-item>
        </ul>
    </li>
</script>

<style>
    .tree-root {
        font-size: 0.95rem;
        color: #1f2937;
        user-select: none;
    }

    .tree-root ul {
        list-style: none;
        padding-left: 1rem;
        margin: 0;
        border-left: 1px dashed #e2e8f0;
    }

    .tree-root > ul {
        padding-left: 0;
        border-left: none;
    }

    .tree-row {
        display: flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 6px;
        cursor: pointer;
        line-height: 1.7;
        transition: background-color 0.12s ease;
    }

    .tree-row:hover {
        background-color: #f1f5f9;
    }

    .tree-row.has-children {
        font-weight: 500;
    }

    .tree-row.active {
        background-color: #e0f2fe;
        color: #126181;
        font-weight: 600;
    }

    .tree-caret,
    .tree-leaf-spacer {
        display: inline-block;
        width: 16px;
        margin-right: 6px;
        text-align: center;
    }

    .tree-caret {
        color: #64748b;
        font-size: 0.75rem;
        transition: transform 0.15s ease;
    }

    .tree-caret.open {
        transform: rotate(90deg);
    }

    .tree-label {
        flex: 1;
        min-width: 0;
    }

    .details-pane {
        padding-left: 1.5rem;
    }

    .details-pane h3 {
        color: #126181;
        margin-bottom: 0.25rem;
    }

    .details-pane .type-line {
        color: #64748b;
        margin-bottom: 1rem;
    }

    .details-pane .attr-row {
        padding: 4px 8px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
    }

    .details-pane .attr-row:last-child {
        border-bottom: none;
    }

    .details-pane .empty-state {
        color: #94a3b8;
        font-style: italic;
    }
</style>

<div id="app">

    <!-- Page-level busy indicator from tiq.components.min.js.
         We use v-if so the component is fully removed from the DOM when idle --
         otherwise it can leave a layout track behind. -->
    <wait-indicator v-if="isBusy" :display="true" mode="Regular"></wait-indicator>

    <div class="row">
        <div class="col-12">
            <h1 class="pb-2 pt-2" style="font-size:2.5rem; color:#126181;">
                {{pageTitle}}
                <a v-if="true" class="float-end btn btn-sm btn-link mt-2" style="font-size:1rem; color:#126181;" v-bind:href="`/applications/ide?node_ids=${context.std_inputs.script_id}&selected=${context.std_inputs.script_id}`" target="_blank">source</a>
            </h1>
            <hr style="border-color:#126181; border-width:medium;" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-5 tree-root">
            <ul>
                <tree-item v-for="root in treeData"
                           :key="root.id || root.name"
                           :item="root"
                           @on-attribute-click="onAttributeClick"
                           :activeattrid="activeAttrId"></tree-item>
            </ul>
        </div>

        <div class="col-md-6 col-lg-7 details-pane">
            <!-- Empty state -->
            <div v-if="!activeAttrId" class="empty-state">
                Select a node from the tree to see its details.
            </div>

            <!-- Error state -->
            <div v-else-if="selectedError" class="text-danger">
                {{selectedError}}
            </div>

            <!-- Loaded state -->
            <div v-else-if="selected">
                <h3>{{selected.displayName}}</h3>
                <div class="type-line">
                    Type: <strong>{{selected.type ? selected.type.displayName : '—'}}</strong>
                </div>

                <h5>Attributes</h5>
                <ul v-if="selected.attributes && selected.attributes.length" class="list-unstyled">
                    <li v-for="attr in selected.attributes" :key="attr.id" class="attr-row">
                        {{attr.displayName}}
                    </li>
                </ul>
                <div v-else class="empty-state">No attributes.</div>
            </div>

            <!-- During loading none of the branches above match; the page-level
                 wait-indicator at the top of the app conveys that work is in flight. -->
        </div>
    </div>
</div>

<script>

var treeItemComponent = {
    template: "#item-template",
    props: {
        item: Object,
        activeattrid: String
    },
    data: function() {
        return {
            isOpen: false
        };
    },
    computed: {
        hasChildren: function() {
            return this.item.children && this.item.children.length;
        }
    },
    methods: {
        toggle: function() {
            if (this.hasChildren) {
                this.isOpen = !this.isOpen;
            }
        },
        onAttributeClick: function(a) {
            this.$emit("on-attribute-click", a);
        }
    }
};

var WinDoc = window.document;

//create instance of the vuejs
var app = createApp({
    data() {
        return {
            pageTitle: "SMIP Model Browser",
            context: <?php echo json_encode($context)?>,
            user: <?php echo json_encode($user)?>,
            treeData: [],
            activeAttrId: null,
            selected: null,
            selectedLoading: false,
            selectedError: null,
        };
    },

    mounted: async function () {
        WinDoc.title = this.pageTitle;
        await this.LoadTreeDataAsync();
    },

    computed: {
        // Page-level busy flag. Today this just reflects the per-instance load,
        // but later topics may add other async work to it.
        isBusy: function () {
            return this.selectedLoading;
        }
    },

    watch: {
        // Whenever the tree highlights a different node, lazy-load that
        // instance's details into the right-hand pane.
        activeAttrId: function (newId) {
            if (newId) {
                this.LoadInstanceAsync(newId);
            } else {
                this.selected = null;
            }
        }
    },

    methods: {
        onAttributeClick: async function(a) {
            console.log("clicked node id:", a);
            if (a != 0) {
                this.activeAttrId = a;
            }
        },
        LoadInstanceAsync: async function(id) {
            this.selectedLoading = true;
            this.selectedError = null;
            this.selected = null;
            try {
                let query = `
                    query loadInstance {
                        object(id: "${id}") {
                            id
                            displayName
                            type { displayName }
                            attributes {
                                id
                                displayName
                            }
                        }
                    }
                `;
                let response = await tiqJSHelper.invokeGraphQLAsync(query);

                // GraphQL servers don't throw on field-level errors -- they return them
                // alongside (possibly null) data. Surface them as errors here.
                if (response && response.errors && response.errors.length) {
                    throw new Error(response.errors.map(e => e.message).join('; '));
                }

                this.selected = (response && response.data && response.data.object) || null;
                if (!this.selected) {
                    this.selectedError = "No data returned for this instance.";
                }
            } catch (err) {
                console.error(err);
                this.selectedError = (err && err.message) || "Failed to load instance.";
            } finally {
                this.selectedLoading = false;
            }
        },
        LoadTreeDataAsync: async function() {

            // Step 1: Get all root instances in the model
            //   partOfId IS NULL       -> object has no parent (a root)
            //   typeId   IS NOT NULL   -> object is a typed instance (skip system folders, etc.)
            let rootsQuery = `
                query q1 {
                    objects(filter: {partOfId: {isNull: true}, and: {typeId: {isNull: false}}}) {
                        id
                        displayName
                        relativeName
                        fqn
                        systemType
                        typeName
                        typeId
                    }
                }
            `;
            let rootsResponse = await tiqJSHelper.invokeGraphQLAsync(rootsQuery);
            let roots = (rootsResponse.data.objects || []).sort((a, b) => (a.fqn.join('/') > b.fqn.join('/')) ? 1 : -1);

            // Step 2: For each root, fetch every object whose idPath contains that root's id
            //         (i.e. every descendant of that root), then fold the flat result into a tree
            //         by walking each item's fqn segment-by-segment.
            let rootNodes = [];
            for (const root of roots) {
                let descendantsQuery = `
                    query q2 {
                        objects(filter: {idPath: {contains: "${root.id}"}}) {
                            id
                            displayName
                            relativeName
                            fqn
                            systemType
                            typeName
                            typeId
                        }
                    }
                `;
                let descendantsResponse = await tiqJSHelper.invokeGraphQLAsync(descendantsQuery);
                let items = (descendantsResponse.data.objects || []).sort((a, b) => (a.fqn.join('/') > b.fqn.join('/')) ? 1 : -1);

                // Seed the tree from the root record itself.
                let rootNode = {
                    name: root.relativeName || (root.fqn && root.fqn[0]),
                    title: root.displayName,
                    children: [],
                    id: root.id
                };

                // Graft every descendant onto the tree using its fqn (array of segments) as the path.
                for (const item of items) {
                    if (item.id === root.id) continue; // root is already represented

                    const segments = item.fqn || [];
                    let currentNode = rootNode;
                    for (let i = 1; i < segments.length; i++) {
                        const seg = segments[i];
                        const isLeaf = (i === segments.length - 1);
                        let child = currentNode.children.find(x => x.name === seg);
                        if (!child) {
                            child = {
                                name: seg,
                                title: isLeaf ? item.displayName : seg,
                                children: [],
                                id: isLeaf ? item.id : 0
                            };
                            currentNode.children.push(child);
                            currentNode.children.sort((a, b) => (a.title > b.title) ? 1 : -1);
                        } else if (isLeaf) {
                            // An ancestor placeholder was created earlier; promote it to a real leaf now.
                            child.title = item.displayName;
                            child.id = item.id;
                        }
                        currentNode = child;
                    }
                }

                rootNodes.push(rootNode);
            }

            // Render the roots as siblings at the top level. No virtual wrapper --
            // every visible row in the tree maps to a real, selectable instance.
            this.treeData = rootNodes;
        }
    },
})
// define the tree-item component
.component("tree-item", treeItemComponent)
// mount
.mount('#app');
</script>
