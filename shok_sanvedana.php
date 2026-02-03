<?php
include("header.php");
?>
<!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com/3.3.5"></script>
<main class="flex-1 px-2 md:px-10  bg-white md:ml-40 mb-20  md:mb-0 max-w-7xl overflow-hidden py-15 ">


            <div class="min-h-screen flex items-center justify-center px-2 md:px-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full  ">

                    <!-- FORM SIDE -->
                    <div class="rounded-md shadow border border-orange-900 px-8 py-6 flex flex-col justify-center md:justify-start"
                        style="background-color:#FFF7E6;">
                        <h2 id="formHeading" class="text-xl font-bold text-orange-800 mb-3 text-center">Create Obituary
                            Card</h2>
                        <form id="shradhanjaliForm" autocomplete="on" class="space-y-4">
                            <div class="flex flex-col md:flex-row gap-3 ">
                                <!-- Profile photo field ) -->
                                <div class="flex-1 items-center mt-7 mb-1">
                                    <label for="photoUpload" id="labelPhoto"
                                        class="block text-orange-700 border-orange-900 border font-semibold mb-1 w-full bg-orange-200 hover:bg-orange-300 rounded p-2 cursor-pointer shadow-lg">

                                    </label>
                                    <input type="file" id="photoUpload" accept="image/*" style="display:none;">
                                </div>
                                <!-- Language select -->
                                <div class="flex-1">
                                    <label id="labelLang" class="block text-orange-700 font-semibold mb-1">Choose
                                        Language</label>
                                    <select id="langSelect"
                                        class="block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500">
                                        <option value="en">English</option>
                                        <option value="hi">हिन्दी</option>
                                        <option value="gu">ગુજરાતી</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Row: Name + Age -->
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="flex-1">
                                    <label id="labelName" class="block text-orange-700 font-semibold">Name</label>
                                    <input type="text" id="name" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                        placeholder="Ex: Hariramji Supa...">
                                </div>
                                <div class="flex-1 ">
                                    <label id="labelAge" class="block text-orange-700 font-semibold">Age</label>
                                    <input type="text" id="age" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                        placeholder="Ex: 92">
                                </div>
                            </div>
                            <!-- Row: Village + Location -->
                            <div class="flex flex-col md:flex-row  gap-3">
                                <div class="flex-1">
                                    <label id="labelVillage" class="block text-orange-700 font-semibold">Village</label>
                                    <input type="text" id="village" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                        placeholder="Ex: Dhajdi">
                                </div>
                                <div class="flex-1">
                                    <label id="labelLocation"
                                        class="block text-orange-700 font-semibold">Location</label>
                                    <input type="text" id="location" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                        placeholder="Ex: Mu. Dhajdi, Ta. Savarkundla, Dist Amreli">
                                </div>
                            </div>
                            <!-- Row: Samadhi tithi + vidhi tithi -->
                            <div class="flex flex-col md:flex-row  gap-3">
                                <div class="flex-1">
                                    <label id="labelSamadhi" class="block text-orange-700 font-semibold">Samadhi
                                        Date</label>
                                    <input type="date" id="samadhi" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500">
                                </div>
                                <div class="flex-1">
                                    <label id="labelVidhi" class="block text-orange-700 font-semibold">Samadhi Ceremony
                                        Date/Time</label>
                                    <input type="text" id="vidhi" required
                                        class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                        placeholder="Ex: 08/11/2025, 8am">
                                </div>
                            </div>
                            <!-- Family row -->
                            <div>
                                <label id="labelFamily" class="block text-orange-700 font-semibold">Family
                                    Names/Numbers</label>
                                <textarea id="family" rows="2" required
                                    class="mt-1 block w-full rounded border border-orange-900 py-2 px-3 focus:outline-orange-500"
                                    placeholder="Ex: Ramaji Hariyani: 87892..., Jagjivbhai..."></textarea>
                            </div>
                            <!-- Buttons row -->
                            <div class="flex gap-3 flex-col md:flex-row  pt-3 justify-center">
                                <button type="submit" id="generateBtn" class="px-6 py-2 rounded shadow bg-orange-600 text-white hover:bg-orange-700 transition">
                                    Generate Card
                                </button>
                                <button type="button" id="downloadBtn" class="px-5 py-2 rounded shadow bg-orange-600 text-white hover:bg-orange-700 transition  ">
                                    Download
                                </button>
                                <button type="button" id="deleteBtn" class="px-5 py-2 rounded shadow bg-red-600 hover:bg-red-700 transition text-white">
                                    Delete
                                </button>

                            </div>
                        </form>
                    </div>

                    <!-- CARD SIDE -->
                    <div class="relative w-full mx-auto pt-16 pb-8 px-6 flex flex-col items-center rounded-md shadow-2xl"
                        style="background-image: url('images/download.jpeg'); background-size: 100% 100%; background-position: center;"
                        id="cardContent">
                        <!-- Title -->
                        <div class="absolute  top-7 transform  z-10">
                            <div id="cardTitle" class="rounded-lg  font-bold text-lg "
                                style=" color:#9F3C09;">
                                Heartfelt Tribute</div>
                        </div>
                        <!-- Faint logo background -->
                        <div class="absolute bottom-12 t z-0 w-60 h-60 pointer-events-none flex items-center justify-center"
                            style="opacity:0.13;">
                            <img src="images/logo.png" alt="Faint Logo" class="object-contain w-full h-full" />
                        </div>
                        <!-- photon + Mala + agarbatti  -->
                        <div class="relative w-70 md:w-90 mt-2 mb-2 flex items-center justify-center" id="cardProfileArea"
                            style="height:154px;">
                            <img src="images/agarbatti.png" alt="Agarbatti" class="absolute left-0 top-[70px] md:top-[58px] h-32 md:h-40">
                            <img src="images/agarbatti.png" alt="Agarbatti" class="absolute right-0 top-[70px] md:top-[58px] h-32 md:h-40"
                                style="transform:scaleX(-1);">
                            <!-- Profile image  -->
                            <img id="cardProfileImg"  
                                class="w-[130px] h-[130px] md:w-[150px] md:h-[150px] object-cover rounded-full border-4 border-orange-800 shadow">
                            <img src="images/shradhanjali.png" alt="Mala"
                                class="absolute  -bottom-12  h-40 w-45 md:w-48 pointer-events-none">
                        </div>
                        <!-- Card Info  -->
                        <div class="w-full rounded-xl py-4 px-4 text-center z-10" id="cardTextArea">
                            <!-- Demo content initially -->
                            <div >
                                <div class="inline-block rounded-md px-4 py-2 font-bold text-md text-orange-900 flex justify-center items-center text-md">
                                    Samajik Sadagyashri [Name]
                                </div>
                            </div>
                            <div class="mt-1 text-gray-800 text-md">(Age: [Age]) (Village: [Village])</div>
                            <div class="mt-1 text-gray-800 text-md">Samadhi: [Samadhi Date]</div>
                            <hr class="my-2 border-orange-400">
                            <div class="text-gray-900 mb-2 text-md">May the Supreme Almighty grant eternal peace to the
                                divine soul.</div>
                            <hr class="my-2 border-orange-300">
                            <div class="text-red-600 font-semibold text-md">Samadhi Ceremony: [Ceremony Date/Time] at
                                [Village]</div>
                            <div class="mt-1 text-orange-700 font-medium text-sm">[Family details...]</div>
                            <div class="mt-1 text-orange-700 text-sm">Location: [Location]</div>
                        </div>
                    </div>
                </div>
            </div>

           <script>
/* Robust multilingual handlers + card generate/download/delete wiring
   Replace your existing DOMContentLoaded script with this block.
*/
document.addEventListener("DOMContentLoaded", function () {
    // state
    let selectedLang = "en";
    let profileImg = "images/logo.png";
    let isGenerated = false;

    // demo / defaults
    const DEMO = {
        name: "[Name]",
        age: "[Age]",
        village: "[Village]",
        samadhi: "[Samadhi Date]",
        vidhi: "[Ceremony Date/Time]",
        family: "[Family details...]",
        location: "[Location]",
    };

    // language map (kept same keys/strings as your original)
    const LANGUAGES = {
        en: {
            formHeading: "Create Obituary Card",
            labelLang: "Choose Language",
            labelName: "Name",
            labelAge: "Age",
            labelVillage: "Village",
            labelSamadhi: "Samadhi Date",
            labelVidhi: "Samadhi Ceremony Date/Time",
            labelFamily: "Family Names/Numbers",
            labelLocation: "Location",
            labelPhoto: "Choose Photo",
            generateBtn: "Generate Card",
            downloadBtn: "Download",
            deleteBtn: "Delete",
            cardTitle: "Heartfelt Tribute",
            prayer: "May the Supreme Almighty grant eternal peace to the divine soul.",
            demoCard: function () {
                return `
                <div class="mt-8">
                  <div class="inline-block rounded-md  font-bold text-md text-orange-900 flex justify-center items-center text-lg">
                    Samajik Sadagyashri ${DEMO.name}
                  </div>
                </div>
                <div class="mt-1 text-gray-800 text-sm md:text-md">(Age: ${DEMO.age}) (Village: ${DEMO.village})</div>
                <div class="mt-1 text-gray-800 text-sm md:text-md ">Samadhi: ${DEMO.samadhi}</div>
                <hr class="my-2 border-orange-400">
                <div class="text-gray-900 mb-2 text-sm md:text-md">${LANGUAGES.en.prayer}</div>
                <hr class="my-2 border-orange-300">
                <div class="text-red-600 font-semibold text-sm md:text-md">Samadhi Ceremony: ${DEMO.vidhi} at ${DEMO.village}</div>
                <div class="mt-1 text-orange-700 font-medium text-xs md:text-sm">${DEMO.family}</div>
                <div class="mt-1 text-orange-700 text-xs md:text-sm">Location: ${DEMO.location}</div>
                `;
            }
        },
        hi: {
            formHeading: "श्रद्धांजलि कार्ड बनाएँ",
            labelLang: "भाषा चुनें",
            labelName: "नाम",
            labelAge: "उम्र",
            labelVillage: "गांव",
            labelSamadhi: "समाधि तिथि",
            labelVidhi: "समाधि विधि तिथि/समय",
            labelFamily: "परिवारजन नाम/नंबर",
            labelLocation: "स्थान",
            labelPhoto: "फोटो चयन करें",
            generateBtn: "कार्ड बनाएँ",
            downloadBtn: "डाउनलोड",
            deleteBtn: "डिलीट",
            cardTitle: "भावभरी श्रद्धांजलि",
            prayer: "परम कृपालु परमात्मा दिव्य आत्मा ने परम शांति आपे एवी प्रभु पासे प्रार्थना",
            demoCard: function () {
                return `
                <div class="mt-8">
                  <div class="inline-block rounded-md font-bold text-md text-orange-900 flex justify-center items-center text-lg">
                    सामाजिक सादग्यश्री ${DEMO.name}
                  </div>
                </div>
                <div class="mt-1 text-gray-800 text-sm md:text-md">(उ.वर्ष.-${DEMO.age}) (गाम-${DEMO.village})</div>
                <div class="mt-1 text-gray-800 text-sm md:text-md">समाधि: ${DEMO.samadhi}</div>
                <hr class="my-2 border-orange-400">
                <div class="text-gray-900 mb-2 text-sm md:text-md">${LANGUAGES.hi.prayer}</div>
                <hr class="my-2 border-orange-300">
                <div class="text-red-600 font-semibold text-sm md:text-md">समाधि विधि: ${DEMO.vidhi} पर, ${DEMO.village} मुकामे राखेल छे</div>
                <div class="mt-1 text-orange-700 font-medium text-xs md:text-sm">${DEMO.family}</div>
                <div class="mt-1 text-orange-700 text-xs md:text-sm">स्थान: ${DEMO.location}</div>
                `;
            }
        },
        gu: {
            formHeading: "શ્રદ્ધાંજલિ કાર્ડ બનાવો",
            labelLang: "ભાષા પસંદ કરો",
            labelName: "નામ",
            labelAge: "ઉંમર",
            labelVillage: "ગામ",
            labelSamadhi: "સમાધિ તારીખ",
            labelVidhi: "સમાધિ વિધી તારીખ/સમય",
            labelFamily: "પરિવારજન નામ/નંબર",
            labelLocation: "સ્થાન",
            labelPhoto: "ફોટો પસંદ કરો",
            generateBtn: "કાર્ડ બનાવો",
            downloadBtn: "ડાઉનલોડ",
            deleteBtn: "ડિલીટ",
            cardTitle: "ભાવભરી શ્રદ્ધાંજલિ",
            prayer: "પારમ કૃપાળુ પરમાત્મા દિવ્ય આત્માને પરમ શાંતિ આપે તેવી પ્રભુ પાસે પ્રાથના",
            demoCard: function () {
                return `
                <div class="mt-8">
                  <div class="inline-block rounded-md  font-bold text-md text-orange-900 flex justify-center items-center text-lg">
                    સમાધિસ્થ સાધુશ્રી ${DEMO.name}
                  </div>
                </div>
                <div class="mt-1 text-gray-800 text-sm md:text-md">(ઉ.વર્ષ.-${DEMO.age}) (ગામ-${DEMO.village})</div>
                <div class="mt-1 text-gray-800 text-sm md:text-md">સમાધિ: ${DEMO.samadhi}</div>
                <hr class="my-2 border-orange-400">
                <div class="text-gray-900 mb-2 text-sm md:text-md">${LANGUAGES.gu.prayer}</div>
                <hr class="my-2 border-orange-300">
                <div class="text-red-600 font-semibold text-sm md:text-md">સમાધિ વિધી: ${DEMO.vidhi}ના રોજ, ${DEMO.village} મુકામે રાખેલ છે</div>
                <div class="mt-1 text-orange-700 font-medium text-sm">${DEMO.family}</div>
                <div class="mt-1 text-orange-700 text-sm">સ્થાન: ${DEMO.location}</div>
                `;
            }
        }
    };

    // helper to safely set textContent if element exists
    function setText(id, text) {
        const el = document.getElementById(id);
        if(el) el.textContent = text;
    }
    // helper to safely set placeholder if element exists
    function setPlaceholder(id, text) {
        const el = document.getElementById(id);
        if(el) el.placeholder = text;
    }

    // Update labels & placeholders & card preview
    function updateLabels(lang) {
        if(!LANGUAGES[lang]) lang = 'en';
        selectedLang = lang;
        const cfg = LANGUAGES[lang];

        // labels
        setText("formHeading", cfg.formHeading);
        setText("labelLang", cfg.labelLang);
        setText("labelName", cfg.labelName);
        setText("labelAge", cfg.labelAge);
        setText("labelVillage", cfg.labelVillage);
        setText("labelSamadhi", cfg.labelSamadhi);
        setText("labelVidhi", cfg.labelVidhi);
        setText("labelFamily", cfg.labelFamily);
        setText("labelLocation", cfg.labelLocation);

        // photo label: keep an icon + text (use innerHTML)
        const ph = document.getElementById("labelPhoto");
        if(ph){
            ph.innerHTML = '<i class=\"fa-solid fa-image mr-2\"></i> ' + cfg.labelPhoto;
        }

        // buttons
        setText("generateBtn", cfg.generateBtn);
        setText("downloadBtn", cfg.downloadBtn);
        setText("deleteBtn", cfg.deleteBtn);

        // placeholders (inputs)
        setPlaceholder("name", cfg.labelName + "...");
        setPlaceholder("age", cfg.labelAge + "...");
        setPlaceholder("village", cfg.labelVillage + "...");
        setPlaceholder("location", cfg.labelLocation + "...");
        setPlaceholder("vidhi", cfg.labelVidhi + "...");

        // card title and preview
        const ct = document.getElementById("cardTitle");
        if(ct) ct.textContent = cfg.cardTitle;

        const preview = document.getElementById("cardTextArea");
        if(preview){
            // use language-specific demoCard
            preview.innerHTML = cfg.demoCard();
        }
    }

    // initial update: use select's current value if present
    const langSelectEl = document.getElementById("langSelect");
    if(langSelectEl){
        selectedLang = langSelectEl.value || selectedLang;
    }
    updateLabels(selectedLang);

    // change listener
    if(langSelectEl){
        langSelectEl.addEventListener("change", function(e){
            updateLabels(e.target.value);
        });
    }

    // Photo upload preview
    const photoInput = document.getElementById('photoUpload');
    if(photoInput){
        photoInput.addEventListener('change', function (e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function (ev) {
                    profileImg = ev.target.result;
                    const cardImg = document.getElementById('cardProfileImg');
                    if(cardImg) cardImg.src = profileImg;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Generate card — use event listener (keeps form behavior clear)
    const form = document.getElementById('shradhanjaliForm');
    if(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            const d = {
                name: document.getElementById('name').value || DEMO.name,
                age: document.getElementById('age').value || DEMO.age,
                village: document.getElementById('village').value || DEMO.village,
                samadhi: document.getElementById('samadhi').value || DEMO.samadhi,
                vidhi: document.getElementById('vidhi').value || DEMO.vidhi,
                family: document.getElementById('family').value || DEMO.family,
                location: document.getElementById('location').value || DEMO.location,
                lang: selectedLang
            };

            // templates object: try to use templates[selectedLang], fallback to LANGUAGES demo
form.addEventListener('submit', function(e){
    e.preventDefault();

    const d = {
        name: document.getElementById('name').value,
        age: age.value,
        village: village.value,
        samadhi: samadhi.value,
        vidhi: vidhi.value,
        family: family.value,
        location: document.getElementById('location').value
    };

    const TEXT = {
        en: {
            title: "Samajik Sadagyashri",
            age: "Age",
            village: "Village",
            samadhi: "Samadhi",
            ceremony: "Samadhi Ceremony",
            location: "Location"
        },
        hi: {
            title: "सामाजिक सादग्यश्री",
            age: "उ.वर्ष",
            village: "गांव",
            samadhi: "समाधि",
            ceremony: "समाधि विधि",
            location: "स्थान"
        },
        gu: {
            title: "સમાધિસ્થ સાધુશ્રી",
            age: "ઉ.વર્ષ",
            village: "ગામ",
            samadhi: "સમાધિ",
            ceremony: "સમાધિ વિધી",
            location: "સ્થાન"
        }
    };

    const t = TEXT[selectedLang];

    cardTextArea.innerHTML = `
        <div>
            <div class="inline-block rounded-md px-4 py-2 font-bold text-md text-orange-800">
                ${t.title} ${d.name}
            </div>
        </div>

        <div class="mt-1 text-gray-800 text-md">
            (${t.age}: ${d.age}) (${t.village}: ${d.village})
        </div>

        <div class="mt-1 text-gray-800 text-md">
            ${t.samadhi}: ${d.samadhi}
        </div>

        <hr class="my-2 border-orange-400">

        <div class="text-gray-900 mb-2 text-md">
            ${LANGUAGES[selectedLang].prayer}
        </div>

        <hr class="my-2 border-orange-300">

        <div class="text-red-600 font-semibold text-md">
            ${t.ceremony}: ${d.vidhi} (${d.village})
        </div>

        <div class="mt-1 text-orange-700 font-medium text-sm">
            ${d.family}
        </div>

        <div class="mt-1 text-orange-700 text-sm">
            ${t.location}: ${d.location}
        </div>
    `;

    cardProfileImg.src = profileImg;
    isGenerated = true;
});



            const cardImg = document.getElementById('cardProfileImg');
            if(cardImg) cardImg.src = profileImg;

            isGenerated = true;
        });
    }

    // Download (only if generated)
    const downloadBtn = document.getElementById('downloadBtn');
    if(downloadBtn){
        downloadBtn.addEventListener('click', function(){
            if(!isGenerated){
                alert('Please generate the card first.');
                return;
            }
            html2canvas(document.getElementById('cardContent'), { useCORS: true, scale: 2 }).then(function (canvas) {
                let link = document.createElement('a');
                link.download = 'shradhanjali-card.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    }
    // Delete resets preview + form
    const deleteBtn = document.getElementById('deleteBtn');
    if(deleteBtn){
        deleteBtn.addEventListener('click', function(){
            updateLabels(selectedLang); // reset preview to demo for current lang
            profileImg = "images/logo.png";
            const cardImg = document.getElementById('cardProfileImg');
            if(cardImg) cardImg.src = profileImg;
            if(form) form.reset();
            isGenerated = false;
        });
    }
});
</script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>


        </main>
        
        </body>
        </html>