# Intro to Mini-IDE, GraphQL, and Vue

This topic introduces the foundational building blocks for writing scripts in the SMIP: the **Mini-IDE** (where scripts live and run), **GraphQL** (how scripts read and write model data), and **Vue.js** (how scripts render reactive UI). The scripts in this folder form a progression — from a bare-metal `fetch` call up to a recursive component-driven model browser, and they introduce the two flavors of script you will encounter throughout the course: **Browser Scripts** and **Display Scripts**.

## Concepts

A script in the SMIP is a `.php` file that is rendered by Joomla and served to the browser. The PHP side is mostly used to register ThinkIQ assets (`tiq.core.js`, `tiq.tiqGraphQL.js`, `tiq.components.min.js`, `tiq.charts.min.js`) and to hand context (the current user, the script id, std_inputs) into the page. Once delivered, the script behaves like any other modern web page: HTML for layout, JavaScript for behavior, and the SMIP GraphQL endpoint at `/api/graphql/` for data.

We will use Vue 3's `createApp({...}).mount('#app')` pattern throughout. Anything bound to the `data()` block of the Vue app becomes reactive — change it, and the DOM updates.

## Browser Scripts vs. Display Scripts

This distinction matters and it is easy to miss. Both are authored in the Mini-IDE, both are `.php` files, and both end up running JavaScript in the user's browser — but they are invoked very differently and they receive very different context.

A **Browser Script** is a standalone page. The user navigates to it directly (typically from the Applications menu, a dashboard tile, or a bookmarked URL). It is not bound to any particular model instance — it stands on its own and decides for itself what data to fetch. Use Browser Scripts for landing pages, reports, dashboards, configuration tools, and anything where the entry point is the script itself rather than a particular thing in the model.

A **Display Script** is bound to a **Type** in the model. Once attached to a type, it appears as an extra **tab** on every **instance** of that type in the Model Explorer. For example, a display script named "Tank Status" defined on a `Storage Tank` type would appear as a tab on every instance of `Storage Tank` (e.g., `Tank-01`, `Tank-02`, `Tank-03`, …), right alongside the built-in tabs Overview, Attributes, Scripts, Relationships, and Material. When the user clicks the tab, the SMIP renders the display script and hands it the **id of the instance they are viewing** via `context.std_inputs.node_id`. The script's whole job is to render a contextual view of *that one instance* — for a tank, perhaps the current fill level and the latest temperature reading. Use Display Scripts for instance-level dashboards, edit forms, run logs, parameter editors, and anything that should "live on" the thing it describes.

The mechanical difference is small but important: a Browser Script can ignore `std_inputs.node_id`; a Display Script almost always reads it on mount and queries `object(id: "...")` to load the instance.

## Script 1 — Hello SMIP with GraphQL

[01 Hello SMIP with GraphQL.php](./01%20Hello%20SMIP%20with%20GraphQL.php) is the smallest possible "hello world" against the SMIP. It uses no Vue and no ThinkIQ helpers — just a button, a `<div>`, and a raw `fetch` against the GraphQL endpoint. This script's purpose is to demystify what every later script is doing under the hood.

```js
let query = `
    query q1 {
        quantities {
            displayName
        }
    }
`;

let apiRoute = '/api/graphql/';
let formData = new FormData();
formData.append('query', query);

let fetchQueryResponse = await fetch(apiRoute, { method: 'POST', body: formData });
let data = await fetchQueryResponse.json();
```

Things to notice:

- The query is a plain string. The SMIP supports the standard GraphQL query language; the schema is browseable from the Mini-IDE.
- The request goes out as a `multipart/form-data` POST with a single `query` field — the SMIP accepts that form just like the standard `application/json` form.
- The result is sorted client-side and rendered by appending `<li>` nodes — no framework involved.

## Script 2.1 — Browser Script Template

[02.1 Browser Script Template with Vue GraphQL Context.php](./02.1%20Browser%20Script%20Template%20with%20Vue%20GraphQL%20Context.php) is the recommended starting point for any new **Browser Script**. It introduces three patterns you will see again and again:

1. **Context injection from PHP into Vue.** A `Context` object and the current Joomla user are JSON-encoded by PHP and dropped into the Vue `data()` block:

    ```php
    require_once 'thinkiq_context.php';
    $context = new Context();
    use Joomla\CMS\Factory;
    $user = Factory::getUser();
    ```

    ```js
    data() {
        return {
            context: <?php echo json_encode($context)?>,
            user: <?php echo json_encode($user)?>,
            ...
        }
    }
    ```

    `context.std_inputs.script_id` is especially useful — it is what the "source" link at the top of the page binds to so a user can jump back into the Mini-IDE.

2. **`tiqJSHelper.invokeGraphQLAsync` instead of raw `fetch`.** Once `tiq.core.js` is loaded, you get a helper that handles the POST, parses the response, and surfaces errors consistently:

    ```js
    let aResponse = await tiqJSHelper.invokeGraphQLAsync(query);
    this.quantities = aResponse.data.quantities;
    ```

3. **Reactive rendering via Vue.** Assigning to `this.quantities` re-renders the `v-for` list with no manual DOM work. Note the nested `v-for` over `measurementUnits` — GraphQL is a natural fit for Vue because both are tree-shaped.

Use this file as a copy/paste starting point for new Browser Scripts.

## Script 2.2 — Display Script Template

[02.2 Display Script Template with Vue GraphQL Context.php](./02.2%20Display%20Script%20Template%20with%20Vue%20GraphQL%20Context.php) is the companion template for **Display Scripts** — the scripts that surface as tabs in the Model Explorer for every instance of a type. The PHP boilerplate, the `tiqJSHelper.invokeGraphQLAsync` helper, and the Vue mounting pattern are all identical to Script 2.1. The difference is in what the script *does on mount*:

```js
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
                    type { displayName }
                    childObjects { displayName }
                }
            }
        `;
        let aResponse = await tiqJSHelper.invokeGraphQLAsync(query);
        this.instance = aResponse.data.object;
    }
}
```

Two things to notice:

- `context.std_inputs.node_id` is the id of the instance whose tab the user just clicked. The Browser Script template ignored this field; the Display Script template depends on it.
- The query uses the singular `object(id: "...")` form rather than the plural list query. You are loading exactly one instance — the one in context.

Once attached to a type (via the type's **Scripts** tab in the Model Explorer), this template will appear as a tab on every instance of that type and render `displayName`, the type, and the immediate child objects. From here, customizing it for a real use case is a matter of replacing the GraphQL query and the markup — for example, attaching this template to a `Storage Tank` type and reshaping the query to pull the tank's `Fill Level` and `Temperature` attributes would give you a per-tank status tab in the Model Explorer. Use this file as a copy/paste starting point for new Display Scripts.

## Script 3 — Render a Vue Tree to Browse the Model

[03 Render Vue Tree to Browse Model.php](./03%20Render%20Vue%20Tree%20to%20Browse%20Model.php) is where everything in this topic comes together: a split-pane SMIP model browser. On the left, a recursive Vue tree built from a two-step GraphQL traversal of the model. On the right, a details pane that lazy-loads the selected instance's name, type, and attributes. The whole thing wears the same header (with a "source" link back to the Mini-IDE) as the templates in 02.1 / 02.2.

### Building the tree (two-step traversal)

The data loading is deliberately split into two GraphQL calls:

1. **Fetch the roots.** A first call asks for every object with no parent (`partOfId` is null) but a real type (`typeId` is not null). These are the top-level instances of the model.

    ```graphql
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
    ```

2. **Fan out under each root.** Then, sequentially for each root, a follow-up call asks for every object whose `idPath` *contains* that root's id — i.e. every descendant of that root. The returned objects are the nodes that go into that root's tree.

    ```graphql
    query q2 {
        objects(filter: {idPath: {contains: "163389"}}) {
            id
            displayName
            relativeName
            fqn
            systemType
            typeName
            typeId
        }
    }
    ```

The two-call pattern is deliberate: it lets each root's subtree render as soon as its descendants come back, instead of waiting for one giant query to resolve, and it keeps every query bounded to a single subtree of the model.

`idPath` and `fqn` are the two breadcrumbs the SMIP gives you for assembling a flat result list into hierarchy — `idPath` is what you filter on to get a subtree, `fqn` is the array of name segments from root to leaf that you walk to place each node under its parent (no string-splitting needed — it comes back as `string[]`). The roots are rendered as siblings at the top level rather than wrapped in a virtual "Model" container, so every visible row in the tree maps to a real, selectable instance.

### The recursive `tree-item` component

The tree itself is a recursive Vue component: `tree-item` renders one node and then renders itself for every child. Clicks bubble up through `$emit("on-attribute-click", item.id)` so the root app can react to a click anywhere in the tree. The component is registered with `.component("tree-item", treeItemComponent)` and references itself inside its own template (`#item-template`).

This component is a stripped-down adaptation of the official [Vue.js Tree View example](https://vuejs.org/examples/#tree-view). We've kept only what's needed to browse a SMIP model — collapse/expand, a click-to-select event, and modern styling (rotating chevrons, hover background, an active-row pill, dashed indent guides for nested levels). If you want to extend the tree's behavior (adding nodes, drag-and-drop, inline editing, etc.), that example is the canonical starting point.

### The right-hand details pane (lazy load)

Clicking a node in the tree stores its id in `activeAttrId`. A Vue `watch` on `activeAttrId` then triggers `LoadInstanceAsync(id)` — a single GraphQL call that returns the clicked instance's name, type, and attribute names:

```graphql
query loadInstance {
    object(id: "...") {
        id
        displayName
        type { displayName }
        attributes {
            id
            displayName
        }
    }
}
```

The right pane is a small state machine: **empty** (no selection), **error**, or **loaded**. There is no explicit "loading" branch — while a request is in flight, none of the branches match and the page-level wait-indicator at the top of the app conveys that work is happening.

One detail worth pointing out: the handler explicitly checks `response.errors` and re-throws them. GraphQL servers don't throw on field-level errors — they return them in the response alongside (possibly null) `data`. Without the check, an error would silently leave `selected` null and the user would see an empty pane with no explanation.

### The built-in `<wait-indicator>`

For the page-level busy indicator we use ThinkIQ's built-in `<wait-indicator>` component, which lives in `tiq.components.min.js` and has to be explicitly loaded alongside `tiq.core.js`:

```php
HTMLHelper::_('script', 'media/com_thinkiq/js/dist/tiq.components.min.js', array('version' => 'auto', 'relative' => false));
```

```html
<wait-indicator v-if="isBusy" :display="true" mode="Regular"></wait-indicator>
```

Note that we use `v-if` (not just the component's own `:display` prop) so the indicator is fully removed from the DOM when idle. Setting `:display="false"` alone tends to leave a layout track behind — a small but visible gray bar where the spinner was. This is a useful pattern in general when working with web-component-flavored UI primitives: control presence with `v-if`, control state with the component's props.

### Aside: the Bootstrap `.placeholder` collision

While building this, we hit a small footgun worth flagging. A `<div class="placeholder">` we added for empty states ("Select a node from the tree…") rendered as an opaque blue-gray rectangle instead of italic gray text — and on hover the cursor turned into a wait spinner. The reason: **Bootstrap 5 reserves `.placeholder` for skeleton loaders**. Its built-in styles paint the element with `background-color: currentColor`, `opacity: 0.5`, and `cursor: wait`. The text was there in the DOM the whole time; Bootstrap was just painting over it.

Class-name collisions with the loaded Bootstrap version are easy to walk into when authoring browser scripts. Rename to something domain-specific (we used `.empty-state`) and the collision goes away. When something looks visually wrong but the markup looks right, "what is Bootstrap doing to this class name?" is a productive first question.

### What's next

Once you have a tree like this with a working right pane, swapping that right pane for charts, attribute editors, or value-stream viewers becomes mechanical — and that is exactly what later topics will do.

## Suggested Order

Read and run the scripts in order: **01** to demystify the request, **02.1** for the Browser Script starting point, **02.2** for the Display Script starting point (and to internalize the Browser-vs-Display distinction), and **03** to see how a real navigational UI is composed. From here, Topic 02 builds on the same patterns to read and write Types, Instances, and Attributes.
