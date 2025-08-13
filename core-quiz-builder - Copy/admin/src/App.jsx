// Get React hooks from WordPress global
const React = window.React;
const { useState, useEffect } = React;

// Access WordPress API from the global wp object
const apiFetch = window.wp?.apiFetch;

function App() {
  const [quizData, setQuizData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Check if apiFetch is available
    if (!apiFetch) {
      setError('WordPress API not available. Please ensure WordPress scripts are loaded.');
      setIsLoading(false);
      return;
    }

    // This gets the current post's ID from the URL.
    const params = new URLSearchParams(window.location.search);
    const postId = params.get('post');

    if (postId) {
      // Call our new custom API endpoint
      apiFetch({ path: `/core-quiz/v1/quiz/${postId}` })
        .then((data) => {
          setQuizData(data);
          setIsLoading(false);
        })
        .catch((err) => {
          setError(err.message || 'Failed to load quiz data');
          setIsLoading(false);
        });
    } else {
      // This is a new quiz that hasn't been saved yet.
      setIsLoading(false);
      setQuizData({
        title: '',
        results: [],
        questions: []
      });
    }
  }, []);

  if (isLoading) {
    return React.createElement('div', null, 'Loading Quiz Data...');
  }

  if (error) {
    return React.createElement('div', { style: { color: 'red' } }, 'Error: ', error);
  }

  return React.createElement(
    'div',
    null,
    React.createElement('h1', null, 'Core Quiz Builder'),
    React.createElement('hr', null),
    React.createElement('h2', null, 'Raw Quiz Data (for debugging):'),
    React.createElement(
      'pre',
      {
        style: {
          background: '#eee',
          padding: '1rem',
          whiteSpace: 'pre-wrap',
          wordBreak: 'break-all'
        }
      },
      JSON.stringify(quizData, null, 2)
    )
  );
}

export default App;