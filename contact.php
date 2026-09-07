<?php
include("header.php");
?>
<title>Contact Us | Sadhu Vandana</title>

<!-- Main Content -->
<main class="flex-1 px-4 md:px-10 py-12 md:ml-20 mb-13 md:mb-0 max-w-4xl mx-auto">
  <div class="bg-white rounded-2xl shadow-xl border border-orange-100 p-8">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full text-orange-600 mb-4">
        <i class="fa fa-envelope-open text-2xl"></i>
      </div>
      <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Contact Us</h1>
      <p class="mt-2 text-lg text-gray-500">We would love to hear from you. Get in touch with our support team.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
      <!-- Contact Info Card 1: Phone -->
      <div class="flex items-start gap-4 p-5 bg-orange-50/50 rounded-xl border border-orange-100/50">
        <div class="flex items-center justify-center w-12 h-12 bg-orange-500 rounded-lg text-white">
          <i class="fa fa-phone text-lg"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Phone Number</h3>
          <p class="mt-1 text-lg font-bold text-gray-900">+91 9428744488</p>
          <p class="text-sm text-gray-500">Mon to Sat, 10 AM to 6 PM</p>
        </div>
      </div>

      <!-- Contact Info Card 2: Email -->
      <div class="flex items-start gap-4 p-5 bg-orange-50/50 rounded-xl border border-orange-100/50">
        <div class="flex items-center justify-center w-12 h-12 bg-orange-500 rounded-lg text-white">
          <i class="fa fa-envelope text-lg"></i>
        </div>
        <div>
          <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Email Address</h3>
          <p class="mt-1 text-lg font-bold text-gray-900">info@sadhuvandna.co.in</p>
          <p class="text-sm text-gray-500">Usually responds within 24 hours</p>
        </div>
      </div>
    </div>

    <!-- Contact Form (HTML Frontend only) -->
    <div class="border-t border-gray-100 pt-8">
      <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Send Us a Message</h2>
      <form class="flex flex-col gap-4" onsubmit="event.preventDefault(); alert('Thank you for contacting us! We will get back to you shortly.');">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-semibold text-gray-600">Your Name</label>
            <input type="text" placeholder="John Doe" required
              class="border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200 bg-orange-50/30" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm font-semibold text-gray-600">Email Address</label>
            <input type="email" placeholder="john@example.com" required
              class="border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200 bg-orange-50/30" />
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-sm font-semibold text-gray-600">Subject</label>
          <input type="text" placeholder="Inquiry about services" required
            class="border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200 bg-orange-50/30" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-sm font-semibold text-gray-600">Message</label>
          <textarea placeholder="Write your message here..." rows="4" required
            class="border border-orange-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 ring-orange-200 bg-orange-50/30 resize-none"></textarea>
        </div>

        <button type="submit"
          class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-orange-500/20 transition flex items-center justify-center gap-2 mt-2">
          <i class="fa fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>
  </div>
</main>

</body>
</html>
