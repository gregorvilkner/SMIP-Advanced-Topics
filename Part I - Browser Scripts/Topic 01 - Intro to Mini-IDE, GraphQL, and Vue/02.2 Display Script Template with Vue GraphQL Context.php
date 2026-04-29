<?php
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.core.js', array('version' => 'auto', 'relative' => false));
// HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.tiqGraphQL.js', array('version' => 'auto', 'relative' => false));
// HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.components.min.js', array('version' => 'auto', 'relative' => false));
// HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.charts.min.js', array('version' => 'auto', 'relative' => false));
require_once 'thinkiq_context.php';
$context = new Context();
use Joomla\CMS\Factory;
$user = Factory::getUser();
?>
<div id="app">
    <div class="row">
        <div class="col-12">
            <h1 class="pb-2 pt-2" style="font-size:2.5rem; color:#126181;">
                {{pageTitle}}
                <a v-if="true" class="float-end btn btn-sm btn-link mt-2" style="font-size:1rem; color:#126181;" v-bind:href="`/applications/ide?node_ids=${context.std_inputs.script_id}&selected=${context.std_inputs.script_id}`" target="_blank">source</a>
            </h1>
            <hr style="border-color:#126181; border-width:medium;" />
        </div>
    </div>

    <div v-if="instance">
        <h2>{{instance.displayName}} - based on type: {{instance.type.displayName}}</h2>
        <br/>
        <h3>Child Objects</h3>
        <div v-for="aChildObject in instance.childObjects">
            <h4>{{aChildObject.displayName}}</h4>
        </div>
    </div>
</div>
<script>
    var WinDoc = window.document;
    var app = createApp({
        // el: "#app",
        data() {
            return {
                pageTitle: "Instance: ",
                context:<?php echo json_encode($context)?>,
                user:<?php echo json_encode($user)?>,
                instance: null,
            }
        },
        mounted: async function () {
            await this.GetInstanceAsync();
            WinDoc.title = this.pageTitle;
        },
        methods: {
            GetInstanceAsync: async function () {
                let query = `
                    query MyQuery {
                        object(id:"${this.context.std_inputs.node_id}") {
                            displayName
                            type{
                                displayName
                            }
                            childObjects{
                                displayName
                            }
                        }
                    }
                `;
                let aResponse = await tiqJSHelper.invokeGraphQLAsync(query);
                this.instance = aResponse.data.object;
                this.pageTitle = `${this.pageTitle}${this.instance.displayName}`;
            }
        },
    })
        .mount('#app');
</script>
