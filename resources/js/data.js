export const categories = [
    {
        id: 'morning-favorite',
        name: 'Morning Favorite',
        image: 'https://images.unsplash.com/photo-1533089862017-5614ec95e9f2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Start your day right with these delicious breakfast options.'
    },
    {
        id: 'evening-meals',
        name: 'Evening Meals',
        image: 'https://images.unsplash.com/photo-1467003909585-2f8a7270028d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Make every dinner special with our Evening Meals collection.'
    },
    {
        id: 'sweet-treats',
        name: 'Sweet Treats',
        image: 'https://images.unsplash.com/photo-1488477181946-6428a029177b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Indulge in our selection of mouth-watering desserts.'
    },
    {
        id: 'fresh-salads',
        name: 'Fresh Salads',
        image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Crispy, crunchy, and full of flavor.'
    },
    {
        id: 'hearty-mains',
        name: 'Hearty Mains',
        image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Filling dishes for the whole family.'
    },
    {
        id: 'tasty-bites',
        name: 'Tasty Bites',
        image: 'https://images.unsplash.com/photo-1541529086526-db283c563270?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Small bites, big flavor.'
    },
    {
        id: 'sauces-marinades',
        name: 'Sauces & Marinades',
        image: 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'The secret to flavor.'
    },
    {
        id: 'perfect-sides',
        name: 'Perfect Sides',
        image: 'https://images.unsplash.com/photo-1623428187969-5da2dcea5ebf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        description: 'Complete your meal.',
    }
];

export const recipes = [
    {
        id: 'apple-brie-stuffed-chicken',
        categoryId: 'evening-meals',
        title: 'Apple Brie Stuffed Chicken',
        description: 'Butterflied chicken breasts stuffed with brie, green apples, and spinach. Cooked in a skillet with mustard coating.',
        image: 'https://images.unsplash.com/photo-1632778149955-e80f8ceca2e8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '20 min',
        cookTime: '30 min',
        servings: 4,
        ingredients: ['4 Chicken breasts', '1 Brie cheese wheel', '2 Green apples', '2 cups Spinach', 'Dijon mustard'],
        instructions: [
            'Preheat oven to 375°F.',
            'Butterfly the chicken breasts.',
            'Slice apples and brie cheese.',
            'Stuff chicken with cheese, apples, and spinach.',
            'Sear in pan then bake for 20 minutes.'
        ]
    },
    {
        id: 'honey-sesame-chicken',
        categoryId: 'evening-meals',
        title: 'Honey Sesame Chicken and Broccoli',
        description: 'Crispy chicken and tender broccoli coated in a sweet and savory honey sesame sauce, ready in 30 minutes.',
        image: 'https://images.unsplash.com/photo-1552611052-33e04de081de?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '15 min',
        cookTime: '15 min',
        servings: 3,
        ingredients: ['1 lb Chicken breast', '1 head Broccoli', 'Honey', 'Soy sauce', 'Sesame seeds'],
        instructions: [
            'Cut chicken into bite-sized pieces.',
            'Stir fry chicken until golden.',
            'Add broccoli and steam.',
            'Mix honey and soy sauce, pour over chicken.',
            'Garnish with sesame seeds.'
        ]
    },
    {
        id: 'philly-cheesesteak-casserole',
        categoryId: 'evening-meals',
        title: 'Philly Cheesesteak Grilled Cheese Casserole',
        description: 'All the flavors of a Philly cheesesteak sandwich transformed into an easy casserole with layers of bread and melted cheese.',
        image: 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '10 min',
        cookTime: '40 min',
        servings: 6,
        ingredients: ['Ground beef', 'Peppers', 'Onions', 'Provolone cheese', 'Sourdough bread'],
        instructions: [
            'Brown the beef with onions and peppers.',
            'Layer bread in a baking dish.',
            'Top with beef mixture and cheese.',
            'Bake until bubbly.'
        ]
    },
    {
        id: 'cranberry-pistachio-shortbread',
        categoryId: 'sweet-treats',
        title: 'Cranberry Pistachio Shortbread Cookies',
        description: 'Buttery shortbread studded with red cranberries and green pistachios, cut into festive Christmas tree shapes.',
        image: 'https://images.unsplash.com/photo-1576618148400-f54bed99fcf8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '25 min',
        cookTime: '12 min',
        servings: 24,
        ingredients: ['Butter', 'Flour', 'Sugar', 'Dried Cranberries', 'Pistachios'],
        instructions: [
            'Cream butter and sugar.',
            'Mix in flour and nuts/fruit.',
            'Chill dough.',
            'Slice and bake.'
        ]
    },
    {
        id: 'morning-avocado-toast',
        categoryId: 'morning-favorite',
        title: 'Perfect Avocado Toast',
        description: 'The classic breakfast staple with a poached egg and chili flakes.',
        image: 'https://images.unsplash.com/photo-1525351484163-7529414395d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '5 min',
        cookTime: '5 min',
        servings: 1,
        ingredients: ['Sourdough bread', 'Ripe Avocado', 'Egg', 'Chili flakes', 'Salt & Pepper'],
        instructions: [
            'Toast the bread.',
            'Mash avocado with lemon juice.',
            'Poach the egg.',
            'Assemble and season.'
        ]
    },
    {
        id: 'bacon-cheeseburger-casserole',
        categoryId: 'hearty-mains',
        title: 'Bacon Cheeseburger Grilled Cheese Casserole',
        description: 'The ultimate comfort food mashup with layers of bread, cheeseburger filling, and melted cheese baked to golden perfection.',
        image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '20 min',
        cookTime: '45 min',
        servings: 6,
        ingredients: ['Ground beef', 'Bacon', 'Cheddar cheese', 'Biscuits'],
        instructions: [
            'Cook beef and bacon.',
            'Layer baking dish with biscuits.',
            'Top with meat and cheese.',
            'Bake until golden.'
        ]
    },
    {
        id: 'pizza-pasta-bake',
        categoryId: 'hearty-mains',
        title: 'Pizza Pasta Bake',
        description: 'A quick weeknight dinner combining penne pasta, marinara sauce, pepperoni and gooey mozzarella cheese. Baked until bubbly with crispy edges.',
        image: 'https://images.unsplash.com/photo-1516100882582-96c3a05fe590?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '15 min',
        cookTime: '25 min',
        servings: 6,
        ingredients: ['Penne pasta', 'Marinara sauce', 'Pepperoni', 'Mozzarella', 'Parmesan'],
        instructions: [
            'Boil pasta.',
            'Toss with sauce and pepperoni.',
            'Top with cheese.',
            'Bake at 400F for 20 mins.'
        ]
    },
    {
        id: 'hot-cheesy-pizza-dip',
        categoryId: 'tasty-bites',
        title: 'Hot Cheesy Pizza Dip',
        description: 'Layered dip with cream cheese pizza sauce, melted mozzarella, and pepperoni. Perfect for game day.',
        image: 'https://images.unsplash.com/photo-1541745537411-b8046dc6d66c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
        prepTime: '10 min',
        cookTime: '20 min',
        servings: 8,
        ingredients: ['Cream cheese', 'Pizza sauce', 'Mozzarella', 'Mini pepperoni'],
        instructions: [
            'Spread cream cheese in a dish.',
            'Top with sauce and cheese.',
            'Add pepperoni.',
            'Bake until bubbly.'
        ]
    },
    {
        id: 'ultimate-lemon-garlic-butter-salmon',
        categoryId: 'hearty-mains',
        title: 'Ultimate Lemon Garlic Butter Salmon',
        description: 'Tender salmon fillets pan-seared to perfection and smothered in a rich, buttery lemon garlic sauce with fresh herbs. A restaurant-quality meal ready in under 20 minutes, perfect for busy weeknights or special occasions.',
        image: 'https://images.unsplash.com/photo-1519708227418-c8fd9a3a19bd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80',
        prepTime: '10 min',
        cookTime: '15 min',
        servings: 4,
        nutrition: {
            calories: '450',
            protein: '34g',
            fat: '28g',
            carbs: '4g'
        },
        ingredients: [
            '4 (6oz) Salmon fillets, skin on or off',
            'Salt and cracked black pepper, to taste',
            '2 tablespoons Olive oil',
            '4 tablespoons Unsalted butter',
            '4 cloves Garlic, minced',
            '1/4 cup Fresh lemon juice (about 1 lemon)',
            '1/4 cup Vegetable or Chicken broth',
            '1 tablespoon Fresh parsley, chopped',
            '1 tablespoon Fresh Dill, chopped',
            'Lemon slices, for garnish',
            'Red chili flakes (optional)'
        ],
        instructions: [
            'Remove salmon fillets from refrigerator and let them sit at room temperature for 10 minutes. Pat dry with paper towels. Season generously with salt and pepper.',
            'Heat olive oil in a large skillet over medium-high heat. Once hot, add salmon fillets flesh-side down (if skinless) or skin-side down (if skin-on). Sear for 4-5 minutes until golden brown and crispy.',
            'Flip the salmon and cook for another 2-4 minutes until cooked through to your liking. Remove salmon from the pan and set aside on a plate.',
            'In the same pan, reduce heat to medium. Add the butter and melt. Add minced garlic and sauté for 1 minute until fragrant, being careful not to burn.',
            'Pour in the vegetable broth and lemon juice. Let it simmer for 2-3 minutes to reduce slightly.',
            'Stir in the fresh parsley and dill. Add chili flakes if using.',
            'Return the salmon fillets to the pan, spooning the sauce over them to coat evenly. Let them warm through for 1 minute.',
            'Garnish with fresh lemon slices and extra herbs. Serve immediately with steamed vegetables or rice.'
        ]
    }
];

export const articles = [
    {
        id: 'pecan-pie-cheesecake',
        title: 'Pecan Pie Cheesecake',
        description: 'This Pecan Pie Cheesecake combines the richness of cheesecake with the sweetness and nutty topping of pecan pie—a perfect holiday dessert.',
        image: 'https://images.unsplash.com/photo-1509456592530-5d38e33f3fdd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
    },
    {
        id: 'fruity-marshmallow-fudge',
        title: 'Fruity Marshmallow Fudge',
        description: 'A colorful, fruity marshmallow fudge with creamy white chocolate, perfect for parties or a sweet snack.',
        image: 'https://images.unsplash.com/photo-1621236378699-8597fcfcd284?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
    },
    {
        id: 'lasagna-soup',
        title: 'Lasagna Soup',
        description: 'Lasagna Soup combines the flavors of classic lasagna in a warm, hearty soup. It’s topped with a blend of ricotta, mozzarella, and Parmesan for a rich, comforting meal.',
        image: 'https://images.unsplash.com/photo-1547592166-23acbe346499?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
    }
];
