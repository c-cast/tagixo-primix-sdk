import { expect, test } from '../helpers/fixtures.js'

/**
 * The Theme Builder: a template dressed zone by zone. The header belongs to the
 * template, so it opens the layout itself — which is the thing the SDK could not
 * do until layouts became a record type.
 */

test('a template shows its three zones, and the header opens the layout', async ({ page, seed }) => {
    await page.goto('/admin/theme-builder')

    await expect(page.getByText('E2E layout')).toBeVisible()
    // Its condition, in the page builder's own words.
    await expect(page.getByText('All Pages')).toBeVisible()

    const zones = page.locator('.tgx-theme-zone')
    await expect(zones).toHaveCount(3)
    await expect(zones.nth(0)).toContainText('Header')
    // The header was seeded, the footer was not.
    await expect(zones.nth(0)).toContainText('Built')
    await expect(zones.nth(2)).toContainText('Empty')
    // The body of an ordinary template belongs to each page.
    await expect(zones.nth(1)).toContainText('own body')

    await zones.nth(0).getByRole('link').click()
    await page.waitForURL(new RegExp(`type=layouts&id=${seed.layout.id}`))
    expect(page.url()).toContain('scope=header')

    const canvas = page.frameLocator('.vb-builder-canvas-iframe')
    await expect(canvas.locator('[data-component-id="e2e-layout-heading"]').first())
        .toContainText('E2E layout header', { timeout: 20_000 })
})

test('the footer of that template opens empty, on the template itself', async ({ page, seed }) => {
    await page.goto('/admin/theme-builder')

    await page.locator('.tgx-theme-zone').nth(2).getByRole('link').click()
    await page.waitForURL(/scope=footer/)

    // Same record, the other document.
    expect(page.url()).toContain(`type=layouts&id=${seed.layout.id}`)
    await expect(page.locator('#tagixo-vue')).toBeVisible()
})
