import { defineConfig } from 'vitepress'
import mathjax3 from 'markdown-it-mathjax3'

const customElements = [
  'math', 'maction', 'maligngroup', 'malignmark', 'menclose', 'merror', 'mfenced', 'mfrac', 'mi', 'mlongdiv', 'mmultiscripts', 'mn', 'mo', 'mover', 'mpadded', 'mphantom', 'mroot', 'mrow', 'ms', 'mscarries', 'mscarry', 'mscarrygroup', 'msgroup', 'msline', 'mspace', 'msqrt', 'msrow', 'mstack', 'mstyle', 'msub', 'msup', 'msubsup', 'mtable', 'mtd', 'mtext', 'mtr', 'munder', 'munderover', 'semantics', 'math', 'mi', 'mn', 'mo', 'ms', 'mspace', 'mtext', 'menclose', 'merror', 'mfenced', 'mfrac', 'mpadded', 'mphantom', 'mroot', 'mrow', 'msqrt', 'mstyle', 'mmultiscripts', 'mover', 'mprescripts', 'msub', 'msubsup', 'msup', 'munder', 'munderover', 'none', 'maligngroup', 'malignmark', 'mtable', 'mtd', 'mtr', 'mlongdiv', 'mscarries', 'mscarry', 'msgroup', 'msline', 'msrow', 'mstack', 'maction', 'semantics', 'annotation', 'annotation-xml',
]

export default defineConfig({
  markdown: {
    config: (md) => {
      md.use(mathjax3)
    }
  },
  vue: {
    template: {
      compilerOptions: {
        isCustomElement: (tag) => customElements.includes(tag)
      }
    }
  },
  title: 'SearchTweak Docs',
  description: 'Search relevance evaluation platform documentation',
  base: '/docs/',
  cleanUrls: true,
  lastUpdated: false,
  ignoreDeadLinks: true,
  themeConfig: {
    siteTitle: 'SearchTweak',
    logoLink: {
      link: '/',
      target: '_self'
    },
    nav: [
      { text: 'Docs', link: '/overview' },
      { text: 'API', link: '/api/overview' }
    ],
    sidebar: [
      {
        text: 'Docs',
        items: [
          { text: 'Overview', link: '/overview' },
          { text: 'Search Endpoints', link: '/search-endpoints' },
          { text: 'Mapper Code', link: '/mapper-code' },
          { text: 'Search Models', link: '/search-models' },
          { text: 'Search Evaluations', link: '/search-evaluations' },
          { text: 'Judges (AI)', link: '/judges' },
          { text: 'Leaderboard', link: '/leaderboard' },
          { text: 'Evaluation Metrics', link: '/evaluation-metrics' },
          { text: 'Team Management', link: '/team-management' },
          { text: 'Tags', link: '/tags' }
        ]
      },
      {
        text: 'API Reference',
        items: [
          { text: 'Overview', link: '/api/overview' },
          { text: 'List Models', link: '/api/list-models' },
          { text: 'Get Model Details', link: '/api/get-model-details' },
          { text: 'List Evaluations', link: '/api/list-evaluations' },
          { text: 'Get Evaluation Details', link: '/api/get-evaluation-details' },
          { text: 'Get Evaluation Judgements', link: '/api/get-evaluation-judgements' },
          { text: 'Create Evaluation', link: '/api/create-evaluation' },
          { text: 'Start Evaluation', link: '/api/start-evaluation' },
          { text: 'Stop Evaluation', link: '/api/stop-evaluation' },
          { text: 'Finish Evaluation', link: '/api/finish-evaluation' },
          { text: 'Delete Evaluation', link: '/api/delete-evaluation' }
        ]
      }
    ]
  }
})
