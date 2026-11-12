# Project guidance

## Frontend UI components

- The frontend uses shadcn/ui-style primitives. Use the shared Tabs components in `apps/frontend/templates/components/Tabs` for tab interfaces rather than hand-building a separate visual system.
- Use these shadcn/ui Tabs for tab menus and section navigation; keep the shared primitives as the source of truth for their interaction and appearance.
- Keep the shared tab variants consistent. The current rock topo menu uses the `default` variant with a transparent list and black active trigger with white text; the `line` variant uses an underline.
- Preserve keyboard behavior, active state, and accessible tab semantics when adapting tabs to another frontend.
