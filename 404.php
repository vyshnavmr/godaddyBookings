<?php

$pageTitle = "Page Not Found - Godaddy Booking";

$currentPage = "";

include "includes/header.php";

?>

<style>
    .error-page{

        padding:140px 0;

        text-align:center;

    }

    .error-code{

        font-family:'Fraunces',serif;

        font-size:120px;

        font-weight:700;

        color:var(--red);

        line-height:1;

        margin-bottom:10px;

    }

    .error-page h1{

        font-family:'Fraunces',serif;

        font-size:32px;

        font-weight:600;

        color:var(--ink);

        margin-bottom:14px;

    }

    .error-page p{

        color:var(--ink-soft);

        font-size:16px;

        max-width:480px;

        margin:0 auto 36px;

        line-height:1.7;

    }

    .error-actions{

        display:flex;

        justify-content:center;

        gap:16px;

        flex-wrap:wrap;

    }

    .error-btn{

        display:inline-flex;

        align-items:center;

        gap:10px;

        padding:14px 30px;

        border-radius:50px;

        font-weight:600;

        font-size:15px;

        text-decoration:none;

        transition:.2s;

    }

    .error-btn-primary{

        background:var(--red);

        color:#fff;

    }

    .error-btn-primary:hover{

        background:var(--red-dark);

    }

    .error-btn-secondary{

        background:var(--white);

        color:var(--ink);

        border:1px solid var(--line);

    }

    .error-btn-secondary:hover{

        background:var(--cream);

    }

    @media(max-width:600px){

        .error-page{

            padding:90px 0;

        }

        .error-code{

            font-size:80px;

        }

        .error-page h1{

            font-size:24px;

        }

    }
</style>

<section class="error-page">

    <div class="container">

        <div class="error-code">404</div>

        <h1>This page seems to have checked out early.</h1>

        <p>

            The page you're looking for doesn't exist, may have moved, or the link might be broken.
            Let's get you back on track.

        </p>

        <div class="error-actions">

            <a href="index.php" class="error-btn error-btn-primary">

                <i class="fa-solid fa-house"></i> Back to Home

            </a>

            <a href="properties.php" class="error-btn error-btn-secondary">

                <i class="fa-solid fa-magnifying-glass"></i> Browse Properties

            </a>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>