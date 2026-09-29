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
