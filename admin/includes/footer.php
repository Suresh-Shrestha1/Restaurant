<?php
// File: includes/footer.php
?>
<footer class="admin-footer">
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> Restaurant Admin Panel. All rights reserved.</p>
        <p>Thank You !</p>
    </div>
</footer>

<style>
.admin-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 15px 20px;
    margin-top: 30px;
    color: #6c757d;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>

</body>
</html>