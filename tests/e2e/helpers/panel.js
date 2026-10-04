/**
 * Type into a field of the panel the way a person does.
 *
 * `fill()` sets the value and dispatches one input event, which LiVue can miss:
 * the field syncs through its own watcher, and the state the panel saves is the
 * one it holds. Real keystrokes plus a blur leave nothing in doubt.
 *
 * @param {import('@playwright/test').Locator} field
 */
export async function typeInto(field, value) {
    await field.click()
    await field.press('ControlOrMeta+a')
    await field.pressSequentially(value, { delay: 10 })
    await field.blur()
}

/**
 * The switch of a field, addressed by the question it asks.
 *
 * Not `getByLabel()`: in Primix 0.7.17 a toggle's label is not bound to the
 * input it names, so the accessible name is empty. That is a framework bug
 * with a fix of its own; this suite drives the panel it is given.
 *
 * @param {import('@playwright/test').Page} page
 */
export function switchOf(page, question) {
    return page.locator('.primix-field', { hasText: question }).getByRole('switch')
}
