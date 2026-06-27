<footer class="footer">

    <div class="container">

        <div class="row">

            <div class="col-lg-4">

                <h4>About VIC School</h4>

                <p>
                    VIC School is committed to academic excellence,
                    innovation, leadership, and character building.
                </p>

            </div>

            <div class="col-lg-2">

                <h4>Quick Links</h4>

                <ul>

                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.php">Contact</a></li>

                </ul>

            </div>

            <div class="col-lg-3">

                <h4>Admissions</h4>

                <ul>

                    <li><a href="#">Apply Online</a></li>
                    <li><a href="#">Fee Structure</a></li>
                    <li><a href="#">Prospectus</a></li>

                </ul>

            </div>

            <div class="col-lg-3">

                <h4>Contact Us</h4>

                <p>
                    <i class="fa-solid fa-location-dot"></i>
                    School Address Here
                </p>

                <p>
                    <i class="fa-solid fa-phone"></i>
                    +91 XXXXX XXXXX
                </p>

                <p>
                    <i class="fa-solid fa-envelope"></i>
                    info@vicschool.edu.in
                </p>

            </div>

        </div>

        <hr>

        <div class="copyright">

            © 2026 VIC School.
            All Rights Reserved.

        </div>

    </div>

</footer>

<!-- Bootstrap (Local) -->
<script src="assets/js/libs/bootstrap.bundle.min.js"></script>

<!-- AOS (Local) -->
<script src="assets/js/libs/aos.js"></script>

<!-- Swiper (Local) -->
<script src="assets/js/libs/swiper-bundle.min.js"></script>

<!-- GSAP (Local) -->
<script src="assets/js/gsap/gsap.min.js"></script>
<script src="assets/js/gsap/ScrollTrigger.min.js"></script>
<script src="assets/js/gsap/ScrollToPlugin.min.js"></script>
<script src="assets/js/gsap/CustomEase.min.js"></script>
<script>
    // Register GSAP plugins
    gsap.registerPlugin(ScrollTrigger, ScrollToPlugin, CustomEase);
</script>

<!-- Main JS -->
<script src="assets/js/app.js"></script>

<!-- AI Floating Chat Widget -->
<iframe src="/school-app/ai/chat.php" id="aiChatWidget" style="position:fixed; bottom:20px; right:20px; width:400px; height:500px; border:none; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.15); z-index:9999; display:none;"></iframe>
<button onclick="document.getElementById('aiChatWidget').style.display = document.getElementById('aiChatWidget').style.display === 'none' ? 'block' : 'none';" style="position:fixed; bottom:20px; right:20px; width:60px; height:60px; border-radius:50%; background:#0f4c81; color:#fff; border:none; box-shadow:0 4px 10px rgba(0,0,0,0.2); z-index:10000; font-size:24px; cursor:pointer;"><i class="fa-solid fa-message"></i></button>

</body>
</html>
