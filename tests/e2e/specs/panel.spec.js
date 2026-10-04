import { expect, test } from '../helpers/fixtures.js'

/**
 * The panel a customer gets: one section per builder installed, plus the screens
 * the capabilities bring. Nothing here is listed by hand — it all comes from the
 * BuilderTypeRegistry.
 */

test('the navigation shows what the installed builders bring', async ({ page }) => {
    await page.goto('/admin')

    const nav = page.locator('nav, aside').first()

    for (const label of ['Pages', 'Popups', 'Forms', 'Mails', 'Documents', 'Sliders', 'Media', 'Menus', 'Theme Builder', 'Site settings']) {
        await expect(nav.getByText(label, { exact: true }).first()).toBeVisible()
    }

    // Templates are not a section of their own: a layout and a template are the
    // same thing, and the Theme Builder is where you meet it.
    await expect(nav.getByText('Layouts', { exact: true })).toHaveCount(0)
    await expect(nav.getByText('Templates', { exact: true })).toHaveCount(0)
})

test('the record CRUD of the core is off, and the editor is not', async ({ page }) => {
    // The panel manages records now.
    const manage = await page.request.get('/tagixo/manage')
    expect(manage.status()).toBe(404)

    // What the editor needs stays on.
    const config = await page.request.get('/tagixo/builder/config?context=page')
    expect(config.ok()).toBeTruthy()
})

test('a listing shows the records of its type', async ({ page, seed }) => {
    await page.goto('/admin/pages')

    await expect(page.getByText('E2E home')).toBeVisible()
    await expect(page.getByText(seed.page.slug)).toBeVisible()
    await expect(page.getByText('Draft').first()).toBeVisible()
})
