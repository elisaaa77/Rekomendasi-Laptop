from flask import Flask, jsonify, request
from pymongo import MongoClient

app = Flask(__name__)
client = MongoClient('mongodb://localhost:27017/')
db = client['ecommerce']

@app.route('/recommendations/<user_id>')
def recommend(user_id):
    user = db.users.find_one({'_id': user_id})
    if not user:
        return jsonify({'error': 'User not found'}), 404
    
    prefs = user.get('preferences', [])
    products = db.products.find({'tags': {'$in': prefs}}).limit(5)
    result = []
    for p in products:
        result.append({
            'name': p['name'],
            'price': p['price'],
            'tags': p['tags']
        })
    return jsonify(result)

if __name__ == '__main__':
    app.run(debug=True)
