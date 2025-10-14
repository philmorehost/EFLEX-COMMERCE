from playwright.sync_api import sync_playwright, Page, expect
import re

def login(page: Page):
    page.goto("http://localhost:8000/admin/index.php")
    page.locator('input[name="username"]').fill("admin")
    page.locator('input[name="password"]').fill("password")
    page.locator('button[type="submit"]').click()
    expect(page).to_have_url(re.compile(r".*dashboard\.php$"))

def create_attributes(page: Page):
    page.goto("http://localhost:8000/admin/manage_attributes.php")
    page.locator('input[name="name"]').fill("Color")
    page.locator('button[name="add_attribute"]').click()
    color_group = page.locator(".attribute-group", has_text="Color")
    color_group.locator('input[name="value"]').fill("Red")
    color_group.locator('button[name="add_value"]').click()
    page.locator('input[name="name"]').fill("Size")
    page.locator('button[name="add_attribute"]').click()
    size_group = page.locator(".attribute-group", has_text="Size")
    size_group.locator('input[name="value"]').fill("Large")
    size_group.locator('button[name="add_value"]').click()

def create_product(page: Page):
    page.goto("http://localhost:8000/admin/add_product.php")
    page.locator('input[name="name"]').fill("Variant Test Product")
    page.locator('textarea[name="description"]').fill("A product to test variants.")
    page.locator('input[name="price"]').fill("50")
    page.locator('button[name="add_product"]').click()
    url = page.url
    return url.split('id=')[-1]

def manage_variants(page: Page, product_id):
    page.goto(f"http://localhost:8000/admin/edit_product.php?id={product_id}")
    page.locator("#has_variants").check()
    page.locator("#product_attributes").select_option(value=[o.get_attribute("value") for o in page.locator("#product_attributes option") if o.text_content() in ["Color", "Size"]])
    page.locator('button[name="generate_variants"]').click()
    variant_row = page.locator("tr", has_text="Color: Red, Size: Large")
    variant_row.locator('input[name="variant_price[]"]').fill("99.99")
    page.locator('button[name="update_variants"]').click()
    page.reload()
    variant_row = page.locator("tr", has_text="Color: Red, Size: Large")
    expect(variant_row.locator('input[name="variant_price[]"]')).to_have_value("99.99")
    page.screenshot(path="jules-scratch/verification/variant_management.png")

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        # Step 1: Create Attributes
        page1 = browser.new_page()
        login(page1)
        create_attributes(page1)
        page1.close()

        # Step 2: Create Product
        page2 = browser.new_page()
        login(page2)
        product_id = create_product(page2)
        page2.close()

        # Step 3: Manage Variants
        page3 = browser.new_page()
        login(page3)
        manage_variants(page3, product_id)
        page3.close()

        browser.close()

if __name__ == "__main__":
    main()
