from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        page.goto("http://0.0.0.0:8001/index.php")

        # Find the first "Add to Cart" button and click it
        first_add_to_cart_button = page.locator(".ajax-add-to-cart").first
        expect(first_add_to_cart_button).to_be_visible()
        first_add_to_cart_button.click()

        # Wait for the modal to appear
        modal = page.locator("#addToCartModal")
        expect(modal).to_be_visible()

        # Check if the variant selection view is present
        variant_selection_view = page.locator("#variant-selection-view")

        # Use a short timeout to see if variant options appear
        try:
            expect(variant_selection_view.locator(".variant-select")).to_have_count(1, timeout=2000)

            # --- Variant Product Flow ---
            print("Product has variants. Testing variant selection.")

            # Select the first option from each dropdown
            for dropdown in page.locator(".variant-select").all():
                dropdown.select_option(index=1)

            # Click the "Add to Cart" button inside the modal
            page.locator("#modal-add-to-cart-btn").click()

        except AssertionError:
            # --- Simple Product Flow ---
            print("Product does not have variants. Testing simple add to cart.")
            # The item is added automatically for simple products, so we just wait for the confirmation view
            pass

        # Verify the confirmation view is displayed
        confirmation_view = page.locator("#confirmation-view")
        expect(confirmation_view).to_be_visible()

        # Check for confirmation text
        expect(page.locator("#confirm-product-name")).not_to_be_empty()
        expect(page.locator("#confirm-cart-subtotal")).not_to_be_empty()

        # Take a screenshot of the confirmation modal
        page.screenshot(path="jules-scratch/verification/verification.png")
        print("Screenshot taken successfully.")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)