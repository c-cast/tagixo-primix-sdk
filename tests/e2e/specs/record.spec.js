import { expect, test } from '../helpers/fixtures.js'
import { typeInto } from '../helpers/panel.js'

/**
 * A record of a builder, from the panel: the editor opens from its listing, and
 * the metadata is written by the type — which is what makes turning the core's
 * own CRUD off safe.
 */

test('a row opens the builder of the core, and comes back to the listing', async ({ page, seed }) => {
    await page.goto('/admin/pages')
    await page.getByText('E2E home').first().click()

    await page.waitForURL(/\/tagixo\/builder\/embed\?type=pages/)

    const canvas = page.frameLocator('.vb-builder-canvas-iframe')
    await expect(canvas.locator(`[data-component-id="${seed.page.headingId}"]`).first()).toBeVisible({ timeout: 20_000 })
    await expect(canvas.locator(`[data-component-id="${seed.page.headingId}"]`)).toContainText('E2E page heading')

    // The back button of the editor returns to the panel it was opened from
    // (`back` in the mount URL, which the resource put there).
    await page.waitForLoadState('networkidle')
    await page.getByTitle('Exit').click()
    await page.waitForURL(/\/admin\/pages$/)
})

test('the metadata form saves through the type', async ({ page, seed }) => {
    await page.goto(`/admin/pages/${seed.page.id}/edit`)

    const title = page.getByLabel('Title')
    await expect(title).toHaveValue('E2E home')
    await typeInto(title, 'E2E home renamed')
    await page.getByRole('button', { name: /^Save/ }).first().click()

    await expect(page.getByText(/saved/i).first()).toBeVisible()
    await page.goto('/admin/pages')
    await expect(page.getByText('E2E home renamed')).toBeVisible()
})

test('a slug another page already has is refused, by the rules of the type', async ({ page, seed }) => {
    await page.goto(`/admin/pages/${seed.page.id}/edit`)

    await typeInto(page.getByLabel('Slug'), seed.takenPage.slug)
    await page.getByRole('button', { name: /^Save/ }).first().click()

    // The unique rule lives in PageType::updateRules(), and the panel validates with it.
    await expect(page.getByText(/already been taken|already exists/i).first()).toBeVisible()
})
