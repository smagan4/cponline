//define([
  //  "ebg/stock"])
  function testFunctions() {
    // Test case 1
    // Generate an array of numbers from 1 to 52
    let numbers = Array.from({length: 52}, (_, i) => i + 1);

    // Shuffle the array
    for (let i = numbers.length - 1; i > 0; i--) {
        let j = Math.floor(Math.random() * (i + 1));
        [numbers[i], numbers[j]] = [numbers[j], numbers[i]];
    }

    // Slice the first 13 numbers
    let randomNumbers = numbers.slice(0, 13);

    // Set the types of the cards in hand1 to the random numbers
    let hand1 = randomNumbers.map(type => ({ type }));

    let fMap = getFreqMapOfHandValues(hand1) ;
    console.log(fMap);
    console.log(isStraight(fMap));

  //  console.assert(JSON.stringify(getFreqMapOfHandSuits(hand1)) === JSON.stringify([5, 0, 0, 0]));
//console.log('Hello');
    // Add more test cases as needed
}

testFunctions();

function getFreqMapOfHandValues (playerHandArray) {
  
    //take the types of all cards in the hand (which will be 1-52 with 1 being 3 clubs and 52 2 hearts)
    //and generate a frequency map of card values based type
    var freqMapCardValues = new Array(16).fill(0);

    for (let i = 0; i < playerHandArray.length; i++) {
        let inputCardGroup = Math.floor((playerHandArray[i].type - 1) / 4); // Calculate the group number of the input card
        freqMapCardValues[inputCardGroup+3].test = playerHandArray[i].id;
        freqMapCardValues[inputCardGroup+3]++;
    }
    //Move values of 2 back to item 2 of the array and remove item 15
    freqMapCardValues[2] = freqMapCardValues[15];
    freqMapCardValues[15]=0;
    return freqMapCardValues;
}

function getFreqMapOfHandSuits (playerHandArray) {
  
    //take the types of all cards in the hand (which will be 1-52 with 1 being 3 clubs and 52 2 hearts)
    //and generate a frequency map of card values based type
    var freqMapCardSuits = [];

    for (let i = 0; i < playerHandArray.length; i++) {
        //Group into suit values based on the remainder after dividing by 4
        //1 = clubs, 4 = hearts
        let inputCardGroup = (playerHandArray[i].type) % 4; 
        if (inputCardGroup == 0 ) {inputCardGroup = 4;}    
            if (i== inputCardGroup) {
                freqMapCardSuits[i+1]++;
            }
    }
    return freqMapCardSuits;
}


 function isStraight(frequencyMap) {
    // Define the ranks in order
    const ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'];

    // Check for a straight
    for (let i = 0; i <= ranks.length - 5; i++) {
        let slice = ranks.slice(i, i + 5);
        if (slice.every(rank => frequencyMap[rank] >= 1)) {
            // Find the value of the first card in the hand that matches the first rank in the straight
            let firstCard =  slice[0];
            return firstCard;
        }
    }

    // Special case for 5-high straight
    let slice = ranks.slice(0, 4);
    if (slice.every(rank => frequencyMap[rank] >= 1) && frequencyMap['A'] >= 1) {
        // Find the first card in the hand that matches the first rank in the straight
        let firstCard = slice[0];
        return firstCard;
    }

    return false;
}