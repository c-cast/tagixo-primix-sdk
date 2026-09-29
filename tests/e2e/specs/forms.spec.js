import { expect, test } from '../helpers/fixtures.js'

/**
 * What the form builder adds to the panel: a form drawn in the editor, previewed
 * as a real Primix form, and the Table tab a field gains.
 */

test('an app form is previewed as a Primix form, not as HTML of its own', async ({ page, seed }) => {
    await page.goto(`/admin/forms/${seed.form.id}/preview`)

    await expect(page.getByText('E2E form — Preview')).toBeVisible()
    // The field of the form, rendered by Primix and marked for its element styles.
    await expect(page.getByLabel('E2E e-mail')).toBeVisible()
    await expect(page.locator('[data-tgx-field="email"]')).toBeVisible()
})

test('the fields of an app form carry the Table declaration into the editor payload', async ({ page, seed }) => {
    const response = await page.request.get(`/tagixo/form-builder/config?form_id=${seed.form.id}`)
    expect(response.ok()).toBeTruthy()

    const config = await response.json()
    // The payload keys its components by type.
    const field = config.availableComponents['text-input']

    // The capability injected it, and the form's own target (`app`) lets it through.
    expect(Object.keys(field.propTypes)).toContain('table')
    expect(field.propTypes.table.schema.tab).toBe('table')
    expect(config.formTargets).toContain('app')

    // NOTE: the properties drawer of the form builder does not render a tab for a
    // prop type an SDK injects under a tab of its own — the declaration arrives,
    // the tab does not. Asserting the payload is asserting what is true today.
})
