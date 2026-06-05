<nav class="backdrop-blur-md bg-white/10 border border-white/20 shadow-lg px-4 py-3 flex justify-between items-center w-[90%] md:w-[70%] lg:w-[55%] fixed top-4 left-1/2 -translate-x-1/2 z-50 rounded-2xl text-white">

  <h1 class="font-bold text-base md:text-xl">📸 PhotoPlan AI</h1>

  <!-- Desktop nav -->
  <div class="hidden sm:flex items-center gap-2">
    <?php if ((isset($_SESSION["username"])) && ($_SESSION["isAdmin"] == 1)): ?>
        <span class="mr-2 text-white/80 text-sm">Admin: <?= $_SESSION["username"]; ?></span>
        <a href="dashboard.php" class="mr-2 text-sm hover:text-white text-white/70 transition">Dashboard</a>
        <a href="includes/logout.inc.php" class="text-sm text-red-400 hover:text-red-300 transition">Logout</a>
    <?php elseif (isset($_SESSION["username"])): ?>
        <span class="mr-2 text-white/80 text-sm">Hello, <?= $_SESSION["username"]; ?></span>
        <a href="dashboard.php" class="mr-2 text-sm hover:text-white text-white/70 transition">Dashboard</a>
        <a href="logout.inc.php" class="text-sm text-red-400 hover:text-red-300 transition">Logout</a>
    <?php else: ?>
        <a href="../login.php" class="mr-2 text-sm hover:text-white text-white/70 transition">Login</a>
        <a href="../signup.php" class="text-sm hover:text-white text-white/70 transition">Register</a>
    <?php endif; ?>
  </div>

  <!-- Mobile hamburger -->
  <button class="sm:hidden flex flex-col justify-center gap-1.5 p-1 z-[110]" onclick="toggleDrawer()" aria-label="Toggle menu">
    <span class="block w-5 h-0.5 bg-white/80 transition-all duration-300" id="ham-1"></span>
    <span class="block w-5 h-0.5 bg-white/80 transition-all duration-300" id="ham-2"></span>
    <span class="block w-5 h-0.5 bg-white/80 transition-all duration-300" id="ham-3"></span>
  </button>

</nav>

<!-- Backdrop -->
<div
  id="drawer-backdrop"
  onclick="toggleDrawer()"
  class="sm:hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] opacity-0 pointer-events-none transition-opacity duration-300"
></div>

<!-- Drawer -->
<div
  id="mobile-drawer"
  class="sm:hidden fixed top-0 right-0 h-full w-72 z-[100] flex flex-col
         bg-white/10 backdrop-blur-xl border-l border-white/20 text-white
         translate-x-full transition-transform duration-300 ease-in-out"
>

  <!-- Drawer header -->
  <div class="flex items-center justify-between px-5 py-5 border-b border-white/10">
    <span class="font-bold text-lg">📸 PhotoPlan AI</span>
    <button onclick="toggleDrawer()" class="text-white/60 hover:text-white transition text-xl leading-none">&times;</button>
  </div>

  <!-- Drawer links -->
  <nav class="flex flex-col gap-1 px-4 py-5 flex-1">

    <?php if ((isset($_SESSION["username"])) && ($_SESSION["isAdmin"] == 1)): ?>

      <div class="px-3 py-2 mb-2 rounded-xl bg-white/5 border border-white/10 text-sm text-white/60">
        Signed in as <span class="text-white font-semibold"><?= htmlspecialchars($_SESSION["username"]) ?></span>
        <span class="ml-1 text-xs text-blue-400 font-medium uppercase tracking-wider">Admin</span>
      </div>
      <a href="includes/dashboard.php"
         class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
        </svg>
        Dashboard
      </a>
      <div class="mt-auto pt-4 border-t border-white/10">
        <a href="includes/logout.inc.php"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10 transition text-sm font-medium">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
          </svg>
          Logout
        </a>
      </div>

    <?php elseif (isset($_SESSION["username"])): ?>

      <div class="px-3 py-2 mb-2 rounded-xl bg-white/5 border border-white/10 text-sm text-white/60">
        Hello, <span class="text-white font-semibold"><?= htmlspecialchars($_SESSION["username"]) ?></span>
      </div>
      <a href="includes/dashboard.php"
         class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
        </svg>
        Dashboard
      </a>
      <div class="mt-auto pt-4 border-t border-white/10">
        <a href="includes/logout.inc.php"
           class="flex items-center gap-3 px-3 py-3 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10 transition text-sm font-medium">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
          </svg>
          Logout
        </a>
      </div>

    <?php else: ?>

      <a href="login.php"
         class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
        </svg>
        Login
      </a>
      <a href="signup.php"
         class="flex items-center gap-3 px-3 py-3 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
        </svg>
        Register
      </a>

    <?php endif; ?>

  </nav>
</div>

<script>
function toggleDrawer() {
  const drawer   = document.getElementById('mobile-drawer');
  const backdrop = document.getElementById('drawer-backdrop');
  const h1 = document.getElementById('ham-1');
  const h2 = document.getElementById('ham-2');
  const h3 = document.getElementById('ham-3');

  const isOpen = !drawer.classList.contains('translate-x-full');

  if (isOpen) {
    // Close
    drawer.classList.add('translate-x-full');
    backdrop.classList.remove('opacity-100', 'pointer-events-auto');
    backdrop.classList.add('opacity-0', 'pointer-events-none');
    document.body.style.overflow = '';
    h1.style.transform = '';
    h2.style.opacity   = '';
    h3.style.transform = '';
  } else {
    // Open
    drawer.classList.remove('translate-x-full');
    backdrop.classList.remove('opacity-0', 'pointer-events-none');
    backdrop.classList.add('opacity-100', 'pointer-events-auto');
    document.body.style.overflow = 'hidden'; // prevent scroll behind drawer
    h1.style.transform = 'translateY(8px) rotate(45deg)';
    h2.style.opacity   = '0';
    h3.style.transform = 'translateY(-8px) rotate(-45deg)';
  }
}

// Close drawer on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const drawer = document.getElementById('mobile-drawer');
    if (!drawer.classList.contains('translate-x-full')) toggleDrawer();
  }
});
</script>