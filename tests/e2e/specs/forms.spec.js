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

test('a field of an app form gains the Table tab the SDK injected', async ({ page, seed }) => {
    // The capability injects the group, and the form's own target (`app`) lets it
    // through — the payload says so…
    const response = await page.request.get(`/tagixo/form-builder/config?form_id=${seed.form.id}`)
    const config = await response.json()

    expect(Object.keys(config.availableComponents['text-input'].propTypes)).toContain('table')
    expect(config.formTargets).toContain('app')

    // …and the drawer shows it, next to the standard tabs.
    await page.goto(`/tagixo/builder/embed?type=forms&id=${seed.form.id}`)

    const canvas = page.frameLocator('.vb-builder-canvas-iframe')
    await expect(canvas.locator('[data-component-id="e2e-field-email"]').first()).toBeVisible({ timeout: 20_000 })
    await page.waitForLoadState('networkidle')

    await canvas.locator('[data-component-id="e2e-field-email"]').first().dispatchEvent('click')

    const table = page.getByRole('tab', { name: 'Table', exact: true })
    await expect(table).toBeVisible()

    await table.click()
    await expect(page.getByText('Show as column').first()).toBeVisible()
})
