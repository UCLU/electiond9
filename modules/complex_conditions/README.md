Features:

- Allows multiple conditions fields on an entity

---

How to create your own condition plugins:

How to evaluate a conditions field in your custom module:

---

- Create a widget in src/Plugin/Field/FieldWidget that extends one of the existing widgets, with the field_types including conditions_plugin_item:{your plugin type ID} - you do not need to do anything other than extend if the widget is fine

To use in a Base Field Definition, use conditions_plugin_item:{your plugin type ID}
