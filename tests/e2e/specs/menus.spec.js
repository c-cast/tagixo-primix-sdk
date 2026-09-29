import { expect, test } from '../helpers/fixtures.js'
import { typeInto } from '../helpers/panel.js'

/**
 * The item tree of a menu: rows of their own, written by the page builder's
 * persister. What matters here is that a tree edited in the panel comes back the
 * same after a reload.
 */

test('a menu shows its items, and keeps the one added', async ({ page, seed }) => {
    await page.goto(`/admin/menus/${seed.menu.id}/edit`)

    await expect(page.getByLabel('Name')).toHaveValue('E2E menu')

    const labels = page.getByLabel('Label')
    await expect(labels).toHaveCount(1)
    await expect(labels.first()).toHaveValue('E2E first item')

    await page.getByRole('button', { name: /Add an item/i }).click()
    await expect(labels).toHaveCount(2)
    await typeInto(labels.nth(1), 'E2E second item')

    await page.getByRole('button', { name: /^Save/ }).first().click()
    await expect(page.getByText(/saved/i).first()).toBeVisible()

    // The persister wrote rows: a reload reads them back, in order.
    await page.reload()

    await expect(page.getByLabel('Label')).toHaveCount(2)
    await expect(page.getByLabel('Label').nth(0)).toHaveValue('E2E first item')
    await expect(page.getByLabel('Label').nth(1)).toHaveValue('E2E second item')
})
