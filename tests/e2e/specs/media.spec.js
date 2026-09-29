import { expect, test } from '../helpers/fixtures.js'

/**
 * The media library of the core, in the panel: the section and the upload screen.
 * The picker itself is the core's own component, loaded from the script this SDK
 * registers on the LiVue app.
 */

test('the library opens, empty until something is uploaded', async ({ page }) => {
    await page.goto('/admin/media')

    await expect(page.getByRole('heading', { name: 'Media' }).first()).toBeVisible()
    await expect(page.getByRole('link', { name: /Upload/i }).first()).toBeVisible()
})

test('the upload screen asks for a folder and the files', async ({ page }) => {
    await page.goto('/admin/media/upload')

    await expect(page.getByLabel('Folder')).toBeVisible()
    await expect(page.getByText('Files')).toBeVisible()
})

test('the picker script of the core is on the page', async ({ page }) => {
    await page.goto('/admin/media')

    const scripts = await page.locator('script[src*="media-picker"]').count()
    expect(scripts).toBeGreaterThan(0)
})
