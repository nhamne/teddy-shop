<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

</div><!-- .col-full -->
</div><!-- #content -->

<?php do_action('storefront_before_footer'); ?>


	<div>

		<?php
		if (function_exists('storefront_footer_widgets')) {
			storefront_footer_widgets();
		}
		?>

		<!-- FOOTER TEDDY SHOP MỚI -->
		<footer class="teddy-custom-footer">
			<div class="teddy-footer-container">
				<div class="teddy-footer-col">
					<h3 class="teddy-footer-title">🧸 VỀ TEDDY SHOP</h3>
					<p>Thiên đường gấu bông vô cùng đáng yêu dành cho bạn và những người thân yêu. Cam kết chất lượng, mềm mịn và an toàn cho sức khoẻ!</p>
				</div>
				<div class="teddy-footer-col">
					<h3 class="teddy-footer-title">📍 THÔNG TIN LIÊN HỆ</h3>
					<p>🏠 <strong>Địa chỉ:</strong> Trường Đại học Điện Lực, 235 Hoàng Quốc Việt, Hà Nội</p>
					<p>📞 <strong>Hotline:</strong> 0989 211 064</p>
					<p>📧 <strong>Email:</strong> cskh@teddyshop.com</p>
				</div>
				<div class="teddy-footer-col">
					<h3 class="teddy-footer-title">🚚 CHÍNH SÁCH MUA HÀNG</h3>
					<p>✔️ Giao hàng hoả tốc nhanh chóng</p>
					<p>✔️ Tặng kèm thiệp xinh xắn miễn phí</p>
					<p>✔️ Đổi trả trong 7 ngày nếu lỗi NSX</p>
				</div>
			</div>
			<div class="teddy-footer-bottom">
				<p>Bản quyền © 2026 - Teddy Shop. All rights reserved.</p>
			</div>
		</footer>

<?php do_action('storefront_after_footer'); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>