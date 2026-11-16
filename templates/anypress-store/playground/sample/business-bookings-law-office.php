<?php
/**
 * The Law Office demo, an industry demo of the Bookings site type: Alder & Finch Law, a four-lawyer firm in Columbus.
 * One consultation form asks what the client needs, shows only the questions for that and books the right lawyer
 * (routing.php), as in Fluent Forms' guide to law firm booking. Naomi (family law), Theo (business and estate
 * planning) and Marcus (criminal defense) take consultations; Dana runs intake and takes the "not sure" calls.
 *
 * Posts: [title, category, writer, image, body]. In a body, a blank line starts a paragraph, "## " a heading and
 * "- " a list item.
 */
return [
    'name' => 'Alder & Finch Law',
    'tagline' => 'Family, business, estate and criminal law in Columbus',
    'kinds' => ['booking', 'enquiry'],
    // The trade's kit: Ledger (professional services).
    'kit' => 'ledger',
    // The firm's own people, after everyone else ('s0' to 's3' below): [first, last, city, company, bio].
    'staff' => [
        ['Naomi', 'Alder', 'Columbus', 'Alder & Finch Law', 'Family law attorney and founding partner.'],
        ['Theo', 'Finch', 'Columbus', 'Alder & Finch Law', 'Business and estate planning attorney and founding partner.'],
        ['Marcus', 'Reyes', 'Columbus', 'Alder & Finch Law', 'Criminal defense attorney.'],
        ['Dana', 'Whitfield', 'Columbus', 'Alder & Finch Law', 'Office manager. Runs intake and the calendar.'],
    ],
    'writers' => [
        ['Naomi', 'Alder', 'Family law attorney. Founded Alder & Finch in 2014 after eight years in a downtown firm.'],
        ['Theo', 'Finch', 'Helps small businesses and families plan ahead. Former bank counsel, current amateur baker.'],
        ['Marcus', 'Reyes', 'Criminal defense attorney and former public defender. Answers his phone at 2am.'],
        ['Dana', 'Whitfield', 'Office manager. Knows where every file is and how long every wait will be.'],
    ],
    'categories' => [
        'Family law' => 'Divorce, custody, support and the paperwork in between, explained plainly.',
        'Business law' => 'Starting, running and selling a small business without surprises.',
        'Estate planning' => 'Wills, trusts and powers of attorney, and why sooner beats later.',
        'Criminal defense' => 'Your rights, the court process and what to do in the first 24 hours.',
        'Firm news' => 'New faces, new hours and what is happening at the office.',
    ],
    'posts' => [
        ['What to bring to your first consultation', 'Firm news', 3, 'documents folder', "A first meeting goes further when we can see the paperwork. You do not need everything, but the more you bring, the more useful the hour.\n\n## Bring\n\n- Any court papers you have received, even if you are not sure what they mean\n- Contracts, leases or agreements that are part of the problem\n- A list of the people involved, so we can run our conflict check\n- Dates: when things happened, and any deadline you have been given\n\n## You do not need\n\nA neat summary. Bring the pile; sorting it is our job."],
        ['Dissolution or divorce: which one fits?', 'Family law', 0, 'wedding rings', "In Ohio, a couple can end a marriage in two ways. A dissolution is for couples who have already agreed on everything: property, support and parenting. A divorce is for when at least one thing is still in dispute.\n\nA dissolution is usually faster and cheaper, because the court is asked to approve an agreement rather than decide a fight. It also tends to be easier on children.\n\nNot sure you agree on everything? Most couples think they do until they write it down. A consultation is a good place to find out where the gaps are."],
        ['Five things to do before you sign a commercial lease', 'Business law', 1, 'shop front', "A lease is often the biggest contract a small business signs, and the one people read least.\n\n- Check who pays for repairs to the roof, heating and parking lot\n- Find out how the rent can rise, and by how much\n- Ask what happens if you need to leave early\n- Look for a personal guarantee, and try to cap it\n- Get every promise the landlord made in writing\n\nWe review leases for a flat fee, and most reviews take less than a week."],
        ['If you are arrested: the first 24 hours', 'Criminal defense', 2, 'city night street', "Being arrested is frightening. What you do in the first day matters more than almost anything that follows.\n\n## Do\n\n- Stay calm and be polite\n- Say clearly that you want to speak to a lawyer\n- Ask a family member to call us; we answer around the clock\n\n## Do not\n\n- Explain, argue or try to talk your way out of it\n- Agree to a search of your phone, car or home\n- Post anything about it online\n\nSilence is not rudeness. It is your right, and it protects you."],
        ['Do I really need a will?', 'Estate planning', 1, 'writing pen paper', "If you own anything, love anyone or have children under eighteen, yes.\n\nWithout a will, Ohio law decides who gets what, and a court decides who raises your children. A will lets you make those choices yourself, name the person who handles everything, and spare your family a slower and more expensive court process.\n\nA simple will takes about two weeks from our first meeting to signing, and it costs less than most people expect. See our flat fees."],
        ['How child custody decisions are made', 'Family law', 0, 'family walking park', "Ohio courts talk about parental rights and responsibilities rather than custody, but the question is the same: what arrangement is best for the child?\n\nJudges look at the child's relationship with each parent, the child's wishes when they are old enough, each home and school, and how well the parents can work together.\n\nThe most useful thing you can do is keep things calm, keep records, and keep the children out of the conflict. Courts notice parents who put the child first."],
        ['LLC or sole proprietor? A plain answer', 'Business law', 1, 'laptop small business', "If your business could be sued, owes money, or has more than one owner, an LLC is usually worth it.\n\nAn LLC keeps the business's debts separate from your house and savings, as long as you keep the money separate too. A sole proprietorship costs nothing to start, but everything you own is on the line.\n\nForming an LLC is a flat fee with us, operating agreement included. The operating agreement is the part people skip, and the part that matters most when partners disagree."],
        ['We now offer evening consultations', 'Firm news', 3, 'office evening', "Many of our clients cannot leave work for a meeting. From this month, Naomi and Theo take consultations on Tuesday and Thursday evenings until 7pm. Book any of them online."],
        ['What happens at an arraignment', 'Criminal defense', 2, 'courthouse', "The arraignment is usually your first time in front of a judge. It is short, and it is not a trial.\n\nThe judge reads the charge, makes sure you understand it and asks how you plead. Most people plead not guilty at this stage, which keeps every option open while your lawyer sees the evidence. The judge also sets or reviews bond.\n\nCome early, dress as you would for a job interview, and let your lawyer do the talking."],
        ['Power of attorney: the document everyone forgets', 'Estate planning', 1, 'elderly hands', "A will works after you die. A power of attorney works while you are alive but cannot make decisions yourself, after an accident or illness.\n\nWithout one, your family may need to go to court to pay your bills or talk to your doctors. With one, the person you chose can step in the same day.\n\nWe include financial and health care powers of attorney in every estate planning package."],
        ['Splitting the house in a divorce', 'Family law', 0, 'house keys', "The family home is often the biggest asset and the hardest to divide. There are three usual answers: one spouse keeps it and buys the other out, the couple sells and splits what is left, or they keep it together for a while, usually until the children finish school.\n\nEach has costs that are easy to miss: refinancing, taxes on a sale, and what happens if one person stops paying. We walk through the numbers before anyone agrees to anything."],
        ['Contracts every small business should have', 'Business law', 1, 'handshake meeting', "Most business disputes we see started with a handshake. Four documents prevent most of them.\n\n- Terms of service, or terms of sale, for your customers\n- A contractor agreement that says who owns the work\n- A non-disclosure agreement for anyone who sees your numbers\n- An operating or partnership agreement between owners\n\nWe write all four as a starter set for a flat fee."],
        ['Expunging an old record in Ohio', 'Criminal defense', 2, 'paperwork desk', "An old conviction can follow you into job interviews and apartment applications. Ohio lets many people seal or expunge their records after a waiting period.\n\nWhether you qualify depends on the offence, how long ago it was and what has happened since. The process means a court filing and a hearing, and it usually takes a few months.\n\nIf you are not sure whether your record qualifies, book a free call with our intake team and we will check."],
        ['Meet Marcus Reyes', 'Firm news', 3, 'smiling man suit', "Marcus joined us this spring after nine years as a public defender. He has tried more cases than most lawyers twice his age, and he still answers his phone at 2am.\n\nAsk him about his garden. He has opinions about tomatoes."],
        ['Trusts are not just for the wealthy', 'Estate planning', 1, 'family home', "A revocable living trust lets your family skip probate, the court process that settles an estate. Probate is public, slow and costs money. A trust is private and can be settled in weeks.\n\nTrusts make sense for people who own a home, have children from more than one relationship, own property in more than one state or simply want things handled quietly. We explain the trade-offs in a free consultation."],
        ['Child support, explained', 'Family law', 0, 'child drawing', "Ohio sets child support with a worksheet that starts from both parents' incomes and the time each spends with the children. Health insurance, child care and extraordinary costs are added in.\n\nThe worksheet gives a starting number; a court can move from it when there is a good reason. Support can also change later if incomes or schedules change a lot.\n\nBring recent pay stubs and last year's tax return to your consultation and we can show you the worksheet with your own numbers."],
        ['Why we ask who else is involved', 'Firm news', 3, 'office meeting room', "Before we talk about your situation, we ask for the names of the other people involved. It is not nosiness. Lawyers cannot work against a current or former client, so we check every name against our files before anyone shares details.\n\nIt takes a few minutes, and it protects you as much as us."],
        ['Selling your business: start two years early', 'Business law', 1, 'signing contract', "The best sales we see start long before the sale. Buyers pay more for a business with clean books, written contracts and a team that can run it without the owner.\n\nTwo years out, tidy the paperwork. One year out, get a valuation and talk to your accountant about taxes. Six months out, talk to us about the sale agreement. It is the order that keeps the most money in your pocket."],
    ],
    'drafts' => [
        ['Prenups: romantic after all?', 'Family law', 0, "Notes: what a prenup can and cannot do in Ohio, when to sign (well before the wedding), both sides need their own lawyer, common myths."],
    ],
    'scheduled' => [
        ['Year-end checklist for small business owners', 'Business law', 1, 'calendar planning', "Before December 31: update your operating agreement if owners changed, renew contracts that are about to lapse, file your annual reports, and check your insurance still matches what you do."],
    ],
    'comments' => [
        'This list saved me so much worry before my first meeting.',
        'I had no idea dissolution was an option. Booked a consultation.',
        'The lease checklist caught a personal guarantee I almost signed.',
        'Shared this with my son. Thank you for writing it so plainly.',
        'Evening times are a huge help. Thank you!',
        'We finally did our wills after reading this.',
        'Very clear explanation of the arraignment.',
        'How long does an LLC take to set up?',
        'Does the power of attorney cover medical decisions too?',
        'Dana is the reason I stayed calm through all of this.',
    ],
    'replies' => [
        'Glad it helped. See you soon.',
        'Usually about a week once we have the details.',
        'Yes, our packages include a separate health care power of attorney.',
        'Thank you, we will pass that on to Dana.',
        'That is exactly why we wrote it.',
    ],
    'messages' => [
        ['Do you handle adoptions?', 'A question', 'My wife and I are adopting her daughter. Is this something Naomi does?'],
        ['Speaking at our chamber lunch', 'Something else', 'Would Theo speak at our small business lunch in March about contracts? Thirty minutes, lunch on us.'],
        ['Payment plans', 'A question', 'Do you offer payment plans for a divorce? I want to understand the cost before I book.'],
        ['Notary appointment', 'A booking', 'Can I come in to have a document notarized, or do I need a full consultation?'],
        ['Thank you', 'Something else', 'Marcus got my case dismissed last week. I cannot thank your team enough.'],
        ['Parking near the office', 'A question', 'Is there parking at Linden Avenue, or should I take the bus?'],
        ['Updating an old will', 'A booking', 'My parents made wills with another firm in 2009. Can you review and update them?'],
        ['Records request', 'A question', 'I need a copy of my file from my case last year. How do I ask for it?'],
    ],
    'newsletter' => [
        'name' => 'The Alder & Finch Brief',
        'every' => 30,
        'issues' => [
            ['Your first consultation, demystified', 'What to bring and what happens', 'What a first meeting with us looks like, start to finish, and the papers worth bringing.'],
            ['Small business season', 'Leases, LLCs and the contracts that matter', 'Theo on the four contracts every small business should have, and a lease checklist.'],
            ['Know your rights', 'The first 24 hours after an arrest', 'Marcus on what to say, what not to say and who to call.'],
            ['Plan ahead', 'Wills, trusts and the document everyone forgets', 'Why a power of attorney matters as much as a will, and our new estate planning packages.'],
            ['Evening hours are here', 'Consultations until 7pm on Tuesdays and Thursdays', 'More time for people who cannot leave work, plus Marcus joins the firm.'],
            ['Back to school, back to schedules', 'Parenting time through the school year', 'Naomi on shared schedules, holidays and keeping the peace in the autumn.'],
        ],
        'next' => ['Year-end checklist', 'Five things to finish before December', 'For small business owners: contracts, filings and insurance before the year ends.'],
        'draft' => ['Holiday parenting schedules', 'Planning the holidays after a separation', 'How to share the holidays without a fight. (Draft: add the December office hours.)'],
        'welcome' => [
            [0, 'Welcome to Alder & Finch', 'How a consultation works', '<p>Hi {{contact.first_name|there}},</p><p>Thank you for getting in touch. Once a month you will get The Alder & Finch Brief: one plain explanation of the law that touches everyday life, and news from the office.</p><p>You can book a consultation online any time. Tell us what you need and the form books you with the right lawyer.</p><p>Naomi, Theo, Marcus and Dana</p>'],
            [2, 'Before your consultation', 'Bring the papers, not a summary', '<p>Hi {{contact.first_name|there}},</p><p>If you have a consultation coming up, bring any court papers, contracts or letters you have received. You do not need to sort them; that is our job.</p><p>Dana</p>'],
            [6, 'Planning ahead', 'Our estate planning packages', '<p>Hi {{contact.first_name|there}},</p><p>Most people put off their will for years. Our flat-fee packages make it a two-week job, from first meeting to signing.</p><p>Theo</p>'],
        ],
    ],
    // FluentBooking: [host (person index, or 's<n>' for staff), about] and the firm's services, then which services
    // past and coming consultations were for (event indexes, in turn).
    'bookings' => [
        'hosts' => [
            ['s0', 'Family law attorney and founding partner. Divorce, dissolution, custody and support.'],
            ['s1', 'Business and estate planning attorney. LLCs, contracts, leases, wills and trusts.'],
            ['s2', 'Criminal defense attorney and former public defender.'],
            ['s3', 'Office manager. Takes the first call when you are not sure who you need.'],
        ],
        'events' => [
            // [host, title, minutes, price in cents (0 free), location, description]
            [0, 'Family law consultation', 45, 0, 'in_person', 'Divorce, dissolution, custody or support: what your options are and what happens next.'],
            [1, 'Business law consultation', 30, 0, 'online', 'Forming a company, a contract or lease to review, or a dispute with a partner or customer.'],
            [1, 'Estate planning consultation', 30, 0, 'in_person', 'Wills, trusts and powers of attorney, and which of them your family needs.'],
            [2, 'Criminal defense consultation', 30, 0, 'phone', 'A charge, an upcoming court date or an old record to seal. Short notice is fine.'],
            [3, 'Not sure? Talk to our intake team', 20, 0, 'phone', 'Tell us what is happening and we will find the right lawyer.'],
            [0, 'Family law strategy session', 60, 25000, 'in_person', 'For clients with an open case: an hour with Naomi to prepare for mediation or a hearing.'],
        ],
        'past' => [0, 1, 3, 2, 0, 4, 5, 3, 1, 0],
        'coming' => [0, 3, 1, 5, 2, 4],
        'address' => '88 Linden Avenue, Suite 300',
        'hours' => ['Family law hours', 'Business and estate hours', 'Criminal defense hours', 'Intake hours'],
        'notes' => ['We have a mediation date next month.', 'Bringing the lease and the landlord\'s letter.', 'Court date is on the 14th.', '', 'Would like to update our wills after the baby.', ''],
        'cancelled' => 'Cancelled by the client: settled the matter between themselves.',
        'missed' => 'Did not arrive. Dana called to rebook.',
        'category' => 'Consultations',
    ],
    // One form for every consultation (routing.php), as in the guide: what the client needs, that area's questions,
    // the other people involved for the conflict check, then a time on that lawyer's calendar.
    'routing' => [
        'title' => 'Book a consultation',
        'intro' => 'Tell us what you need help with and pick a time. The form books you with the right lawyer.',
        'page' => '/',
        'button' => 'Book my consultation',
        'pick' => 'Pick a time for your consultation',
        // [area, event index, questions: [name, label, text, choice or list, choices]]
        'need' => ['What do you need help with?', [
            ['Family Law', 0, [
                ['family_matter', 'What kind of matter is it?', 'list', ['Divorce or dissolution', 'Custody or parenting time', 'Child or spousal support', 'Prenuptial agreement', 'Something else']],
                ['children', 'Are children under 18 involved?', 'choice', ['Yes', 'No']],
            ]],
            ['Business Law', 1, [
                ['business_name', 'Business name', 'text', null],
                ['business_role', 'Your role in the business', 'list', ['Owner', 'Partner or shareholder', 'Manager', 'Not started yet']],
                ['business_matter', 'What kind of matter is it?', 'list', ['Forming a company', 'A contract or lease', 'A dispute', 'Buying or selling a business', 'Something else']],
            ]],
            ['Estate Planning', 2, [
                ['estate_have', 'Do you have a will or trust now?', 'choice', ['Yes', 'No', 'Not sure']],
                ['estate_for', 'Who is the plan for?', 'list', ['Just me', 'Me and my spouse or partner', 'A parent or relative']],
            ]],
            ['Criminal Defense', 3, [
                ['defense_for', 'Who is this for?', 'choice', ['Me', 'Someone else']],
                ['defense_charge', 'What kind of charge?', 'list', ['Traffic or OVI', 'Misdemeanor', 'Felony', 'Sealing an old record', 'Not charged yet']],
                ['defense_custody', 'Is the person in custody now?', 'choice', ['Yes', 'No']],
            ]],
            ['Not Sure', 4, []],
        ]],
        'parties' => ['Other people involved', 'Names of anyone else in the matter (a spouse, a business partner, the other driver). We check them against our files before we meet.'],
        'about' => ['What is happening?', 'A few lines is plenty. Please do not send confidential details until we have spoken.'],
        'terms' => 'I understand that booking a consultation does not make Alder & Finch my lawyers until we both sign an engagement letter.',
        // [person, area, days ahead (negative: past), hour UTC, answers, other parties, what is happening, status]
        'entries' => [
            [3, 0, 2, 15, ['family_matter' => 'Custody or parenting time', 'children' => 'Yes'], 'Rachel Walsh', 'We separated in the summer and need a parenting schedule that works with my travel for weddings.', 'scheduled'],
            [2, 1, 3, 14, ['business_name' => 'Ramirez Catering', 'business_role' => 'Owner', 'business_matter' => 'A contract or lease'], 'Northgate Properties LLC', 'Our kitchen lease renews in March and the landlord wants a personal guarantee.', 'scheduled'],
            [9, 3, 1, 16, ['defense_for' => 'Someone else', 'defense_charge' => 'Misdemeanor', 'defense_custody' => 'No'], '', 'My nephew was cited after a fight outside a bar. His court date is in two weeks.', 'scheduled'],
            [4, 2, 5, 15, ['estate_have' => 'No', 'estate_for' => 'Me and my spouse or partner'], '', 'We have two kids and a house and no will. It is time.', 'scheduled'],
            [21, 4, 4, 17, [], 'Pacific Tide Rentals', 'A rental company kept my deposit and I do not know if I need a lawyer or small claims court.', 'scheduled'],
            [12, 1, 8, 14, ['business_name' => 'Nair Consulting', 'business_role' => 'Owner', 'business_matter' => 'Forming a company'], '', 'I have been freelancing for three years and want to set up an LLC before I hire.', 'scheduled'],
            [10, 0, -6, 15, ['family_matter' => 'Divorce or dissolution', 'children' => 'No'], 'Marc Dubois', 'We agree on almost everything and want to keep this simple and friendly.', 'completed'],
            [6, 2, -9, 16, ['estate_have' => 'Yes', 'estate_for' => 'A parent or relative'], 'Helen Kim', 'My mother\'s will is from 1998 and she has since remarried.', 'completed'],
            [13, 3, -12, 14, ['defense_for' => 'Me', 'defense_charge' => 'Sealing an old record', 'defense_custody' => 'No'], '', 'A possession charge from eight years ago keeps coming up on background checks.', 'completed'],
            [19, 1, -15, 17, ['business_name' => 'Foster Test Labs', 'business_role' => 'Partner or shareholder', 'business_matter' => 'A dispute'], 'Kyle Benton', 'My co-founder wants to leave and we never wrote an operating agreement.', 'completed'],
            [22, 0, -20, 15, ['family_matter' => 'Prenuptial agreement', 'children' => 'No'], 'Jordan Ellis', 'Getting married in June and we both own businesses.', 'cancelled'],
            [7, 4, -24, 16, [], '', 'My landlord is selling the building and says I have to leave in 30 days.', 'completed'],
        ],
    ],
    // Fluent Support: [person index, title, question, answers, state, days ago]
    'tickets' => [
        [10, 'Signing the dissolution papers', 'When can I come in to sign the final papers?', ['Naomi has them ready. Thursday at 2pm or Friday at 10am, whichever suits you.'], 'closed', 74],
        [4, 'Copy of our signed wills', 'Could you send us scanned copies of the wills for our records?', ['Here they are. The originals stay in our fire safe, and you can collect them any time.'], 'closed', 51],
        [12, 'LLC paperwork from the state', 'The state sent a letter about my new LLC. Is it something I need to act on?', ['That is the confirmation of filing. Nothing to do, just keep it with your company records.'], 'closed', 29],
        [13, 'Hearing date for the record sealing', 'Has the court set a date for my hearing yet?', ['Yes: the 3rd of next month at 9am. Marcus will meet you outside courtroom 4B at 8:45.'], 'closed', 11],
        [2, 'Lease comments', 'Did the landlord reply to our changes on the lease?', ['They accepted the cap on the guarantee and want to talk about the repair clause. Theo will call you this afternoon.'], 'waiting', 3],
        [19, 'Draft operating agreement', 'Can you send the draft before Friday so my partner can read it too?', ['Theo is finishing it today. You will have it by tomorrow morning.'], 'waiting', 1],
        [3, 'Uploading documents', 'Where should I send the school records for the parenting plan?', [], 'new', 0.4],
    ],
    // The satisfaction survey after a consultation: which one.
    'services' => ['Family law', 'Business law', 'Estate planning', 'Criminal defense'],
    'form' => ['template' => 'client_satisfaction_survey_form', 'title' => 'After your consultation'],
    'forms' => [],
    // No video of its own (a law office's would be the firm's, made for it).
    'clip' => null,
    'snippet' => ['After-hours notice', "add_action('wp_body_open', function () {\n    echo '<p class=\"after-hours\">Arrested or need help tonight? Call (614) 555-0199, any time.</p>';\n});"],
    // Its own fees table instead of a price list of the (free) consultations.
    'price_list' => false,
    // Ninja Tables and Ninja Charts: the flat fees clients ask about and the consultations the firm takes.
    'tables' => [
        ['services', 'Flat fees', 'Many of our services have one fixed price, agreed before we start. No hourly surprises.', ['Service' => 'text', 'What it includes' => 'text', 'Flat fee ($)' => 'number'], [
            ['Simple will', 'A will, a meeting to sign it with witnesses, and safe keeping of the original.', 650],
            ['Estate planning package', 'Wills for a couple, financial and health care powers of attorney, and a living will.', 1400],
            ['Revocable living trust', 'The trust, a pour-over will, powers of attorney and moving your home into the trust.', 2600],
            ['LLC formation', 'Filing with the state, your operating agreement and a first-year checklist.', 850],
            ['Commercial lease review', 'A written review of your lease with the changes to ask the landlord for.', 450],
            ['Uncontested dissolution', 'The separation agreement, the filings and your hearing, when you agree on everything.', 1900],
            ['Record sealing', 'Checking you qualify, the court filing and the hearing.', 750],
        ], null],
        ['about', 'Consultations each month', 'First consultations we have held over the last six months, by practice area.', ['Month' => 'text', 'Family' => 'number', 'Business' => 'number', 'Estate' => 'number', 'Criminal' => 'number'], [
            ['June', 21, 12, 9, 14], ['July', 18, 14, 11, 17], ['August', 24, 13, 10, 15], ['September', 27, 16, 14, 13], ['October', 25, 19, 17, 18], ['November', 30, 21, 22, 16],
        ], 'line'],
    ],
    // The public pages: [section title, items] and buttons as [label, page slug, '#anchor' or 'portal'].
    'pages' => [
        // The hero's photo: the blog post whose photo it uses.
        'photo' => 16,
        'hero' => 'Plain answers from lawyers who pick up the phone. Tell us what is going on and book a consultation with the right person, online, in two minutes.',
        'buttons' => [['Book a consultation', '#book'], ['Our services', 'services']],
        'features' => ['How we can help', [
            ['Family law', 'Divorce, dissolution, custody and support, handled calmly and with your children in mind. Naomi Alder.'],
            ['Business law', 'LLCs, contracts, leases and disputes between partners, for small businesses. Theo Finch.'],
            ['Estate planning', 'Wills, trusts and powers of attorney at flat fees, done in about two weeks. Theo Finch.'],
            ['Criminal defense', 'Charges, court dates and sealing old records, with a lawyer who answers at 2am. Marcus Reyes.'],
        ]],
        'blog' => ['Insights', 'From our lawyers'],
        'signup' => ['The Alder & Finch Brief', 'One plain explanation of the law each month, and news from the office. No spam, ever.'],
        'faq' => ['Before you book', [
            ['Does booking make you my lawyers?', 'Not yet. We become your lawyers when we both sign an engagement letter, usually after the consultation.'],
            ['Why do you ask who else is involved?', 'We check every name against our files so we never act against a current or former client. It takes a few minutes.'],
            ['What does a consultation cost?', 'First consultations are free. Many of our services then have a flat fee, agreed before we start.'],
            ['Is what I tell you confidential?', 'Yes. Keep the details for the meeting itself; the booking form only needs a few lines.'],
        ]],
        'cta' => ['Not sure who you need?', 'Book a free 20-minute call with Dana and she will find the right lawyer for you.', [['Book a call', '#book']]],
        'about' => ['The story of Alder & Finch', [
            'Naomi Alder and Theo Finch opened the firm in 2014 with one idea: people should understand their own legal problems, and get a straight answer about what it will cost.',
            'Marcus Reyes joined this year after nine years as a public defender, and Dana Whitfield has run the office, the calendar and the coffee since the first day.',
        ], [
            ['Plain answers', 'We explain your options in everyday words, and put them in writing after every meeting.'],
            ['Fixed prices where we can', 'Wills, LLCs, lease reviews and agreed dissolutions all have one price, agreed before we start.'],
            ['We pick up', 'You get your lawyer\'s direct line, and criminal clients can reach Marcus at any hour.'],
        ]],
        'contact' => [['Email', 'hello@alderfinch.example'], ['Phone', '(614) 555-0142, Monday to Friday, 8:30am to 5:30pm. After hours: (614) 555-0199.'], ['Visit', '88 Linden Avenue, Suite 300, Columbus. Parking in the garage on Third Street.']],
        'services' => ['Practice areas', 'services', 'Four practice areas, each led by a lawyer who does nothing else. First consultations are free.', [
            ['Family law', 'Divorce and dissolution, parenting plans and custody, child and spousal support, prenuptial agreements.'],
            ['Business law', 'Forming an LLC, operating agreements, contracts and leases, partner disputes, buying and selling a business.'],
            ['Estate planning', 'Wills, revocable living trusts, financial and health care powers of attorney, updating old plans.'],
            ['Criminal defense', 'Traffic and OVI, misdemeanors and felonies, court appearances, sealing and expunging records.'],
        ]],
    ],
];
